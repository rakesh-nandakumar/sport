<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Exceptions\SlotUnavailableException;
use App\Http\Controllers\CheckoutController;
use App\Livewire\BookingBuilder;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Venue;
use App\Services\BookingService;
use Carbon\Carbon;
use Database\Seeders\ActivityTypeSeeder;
use Database\Seeders\VenueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected Service $court;

    protected Service $ps5;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-14 09:00:00'); // a Monday

        $this->seed([ActivityTypeSeeder::class, VenueSeeder::class]);
        $this->customer = User::factory()->create(['role_id' => Role::Customer, 'phone' => '0771111111']);
        $this->court = Service::where('name', 'Court A (Main)')->firstOrFail();
        $this->ps5 = Service::where('name', 'PS5 Stations')->firstOrFail();
    }

    public function test_public_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Book any sport');
        $this->get('/venues?activity=futsal')->assertOk()->assertSee('CR7 Futsal Arena');
        $this->get('/venues/cr7-futsal-arena')->assertOk()->assertSee('Court A (Main)');
        $this->get(route('booking.build', $this->court))->assertOk()->assertSeeLivewire(BookingBuilder::class);
    }

    public function test_builder_prices_the_plan_and_hands_off_to_checkout(): void
    {
        $this->actingAs($this->customer);

        Livewire::test(BookingBuilder::class, ['service' => $this->court])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '18:00')
            ->call('incrementSlots')
            ->assertSee('2 hr')
            ->assertSee('Evening peak')
            ->call('checkout')
            ->assertRedirect(route('checkout.show'));

        $draft = session(CheckoutController::SESSION_KEY);
        $this->assertSame(2, $draft['slots']);
        $this->assertSame('2026-09-15 18:00:00', $draft['starts_at']);

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Pay at Venue')
            ->assertSee('Coming soon')
            ->assertSee('Rs 12,500'); // 2 × 5,000 + 25% peak on both blocks
    }

    public function test_builder_requires_a_game_for_gaming_services(): void
    {
        $this->actingAs($this->customer);

        Livewire::test(BookingBuilder::class, ['service' => $this->ps5])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '14:00')
            ->call('checkout')
            ->assertSet('error', 'Choose the game you want to play.')
            ->set('gameId', $this->ps5->games->first()->id)
            ->call('checkout')
            ->assertRedirect(route('checkout.show'));
    }

    public function test_guest_is_sent_to_login_from_checkout(): void
    {
        Livewire::test(BookingBuilder::class, ['service' => $this->court])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '10:00')
            ->call('checkout')
            ->assertRedirect(route('login'));
    }

    public function test_checkout_creates_a_pay_at_venue_booking(): void
    {
        $this->actingAs($this->customer)->withSession([CheckoutController::SESSION_KEY => $this->draft($this->court, '2026-09-15 10:00:00', 1)]);

        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'Sahan J',
            'customer_phone' => '0771234567',
            'payment_method' => 'pay_at_venue',
        ]);

        $booking = Booking::firstOrFail();
        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame(PaymentStatus::Unpaid, $booking->payment_status);
        $this->assertSame(1, $booking->priority);
        $this->assertEquals(5000, $booking->total);
        $this->assertNull(session(CheckoutController::SESSION_KEY));

        $this->get(route('bookings.show', $booking))->assertOk()->assertSee($booking->reference)->assertSee('pay');
        $this->assertCount(1, $this->customer->notifications);
        $this->assertCount(1, $this->court->venue->owner->notifications);
    }

    public function test_unavailable_gateways_are_rejected(): void
    {
        $this->actingAs($this->customer)->withSession([CheckoutController::SESSION_KEY => $this->draft($this->court, '2026-09-15 10:00:00', 1)]);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Sahan J',
            'customer_phone' => '0771234567',
            'payment_method' => 'card',
        ])->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Booking::count());
    }

    public function test_second_pay_at_venue_hold_is_blocked_but_bank_transfer_bumps_it(): void
    {
        $service = app(BookingService::class);
        $start = Carbon::parse('2026-09-15 10:00:00');
        $other = User::factory()->create(['role_id' => Role::Customer]);

        $first = $service->reserve($this->customer, $this->court, $this->court->defaultOption(), $start, 1, PaymentMethod::PayAtVenue, $this->details());

        try {
            $service->reserve($other, $this->court, $this->court->defaultOption(), $start, 1, PaymentMethod::PayAtVenue, $this->details());
            $this->fail('Second pay-at-venue hold should be blocked');
        } catch (SlotUnavailableException) {
        }

        $winner = $service->reserve($other, $this->court, $this->court->defaultOption(), $start->copy()->subMinutes(30)->addMinutes(30), 1, PaymentMethod::BankTransfer, $this->details());

        $this->assertSame(BookingStatus::Bumped, $first->fresh()->status);
        $this->assertSame($winner->id, $first->fresh()->bumped_by_booking_id);
        $this->assertSame(2, $winner->priority);
        $this->assertSame(PaymentStatus::PendingVerification, $winner->payment_status);
        $this->assertCount(1, $winner->payments);
        $this->assertTrue($this->customer->fresh()->notifications->pluck('data.message')->contains(fn ($m) => str_contains($m, 'replaced')));
    }

    public function test_vendor_confirmation_locks_a_hold_against_bumping(): void
    {
        $service = app(BookingService::class);
        $start = Carbon::parse('2026-09-15 10:00:00');
        $other = User::factory()->create(['role_id' => Role::Customer]);

        $hold = $service->reserve($this->customer, $this->court, $this->court->defaultOption(), $start, 1, PaymentMethod::PayAtVenue, $this->details());
        $service->vendorConfirm($hold);
        $this->assertSame(3, $hold->fresh()->priority);

        $this->expectException(SlotUnavailableException::class);
        $service->reserve($other, $this->court, $this->court->defaultOption(), $start, 1, PaymentMethod::BankTransfer, $this->details());
    }

    public function test_capacity_allows_parallel_bookings_on_multi_unit_options(): void
    {
        $service = app(BookingService::class);
        $start = Carbon::parse('2026-09-15 14:00:00');
        $option = $this->ps5->options->firstWhere('name', 'Standard seat'); // capacity 8

        for ($i = 0; $i < 8; $i++) {
            $service->reserve(User::factory()->create(['role_id' => Role::Customer]), $this->ps5, $option, $start, 1, PaymentMethod::PayAtVenue, $this->details(), $this->ps5->games->first());
        }
        $this->assertSame(8, Booking::active()->count());

        $this->expectException(SlotUnavailableException::class);
        $service->reserve($this->customer, $this->ps5, $option, $start, 1, PaymentMethod::PayAtVenue, $this->details(), $this->ps5->games->first());
    }

    public function test_bookings_outside_rules_are_rejected(): void
    {
        $service = app(BookingService::class);

        $this->expectException(SlotUnavailableException::class);
        $this->expectExceptionMessageMatches('/align/');
        $service->reserve($this->customer, $this->court, $this->court->defaultOption(), Carbon::parse('2026-09-15 10:15:00'), 1, PaymentMethod::PayAtVenue, $this->details());
    }

    public function test_customer_can_cancel_and_upload_bank_slip(): void
    {
        $service = app(BookingService::class);
        $booking = $service->reserve($this->customer, $this->court, $this->court->defaultOption(), Carbon::parse('2026-09-16 10:00:00'), 1, PaymentMethod::BankTransfer, $this->details());

        $this->actingAs($this->customer);
        $this->get(route('bookings.index'))->assertOk()->assertSee($booking->reference);

        $this->post(route('bookings.proof', $booking), [
            'proof' => UploadedFile::fake()->image('slip.jpg'),
            'reference' => 'TXN123',
        ])->assertRedirect();
        $this->assertNotNull($booking->fresh()->payments->last()->proof_path);

        $this->post(route('bookings.cancel', $booking), ['reason' => 'Rain'])->assertRedirect();
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_vendor_panel_manages_bookings(): void
    {
        $service = app(BookingService::class);
        $booking = $service->reserve($this->customer, $this->court, $this->court->defaultOption(), Carbon::parse('2026-09-16 10:00:00'), 1, PaymentMethod::BankTransfer, $this->details());
        $vendor = $this->court->venue->owner;

        $this->actingAs($vendor);
        $this->get(route('vendor.dashboard'))->assertOk()->assertSee('CR7 Futsal Arena');
        $this->get(route('vendor.venues.index'))->assertOk();
        $this->get(route('vendor.venues.edit', $this->court->venue))->assertOk();
        $this->get(route('vendor.venues.services.index', $this->court->venue))->assertOk()->assertSee('Court A (Main)');
        $this->get(route('vendor.venues.services.edit', [$this->court->venue, $this->court]))->assertOk();
        $this->get(route('vendor.venues.services.create', $this->court->venue))->assertOk();
        $this->get(route('vendor.bookings.index', ['pending_payment' => 1]))->assertOk()->assertSee($booking->reference);
        $this->get(route('vendor.bookings.show', $booking))->assertOk();
        $this->get(route('vendor.events'))->assertOk()->assertJsonCount(1);

        $this->post(route('vendor.bookings.paid', $booking), ['reference' => 'TXN999'])->assertRedirect();
        $booking->refresh();
        $this->assertSame(PaymentStatus::Paid, $booking->payment_status);
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(3, $booking->priority);

        $this->actingAs($this->customer)->get(route('vendor.dashboard'))->assertForbidden();
    }

    public function test_vendor_can_create_a_service_with_options_and_rates(): void
    {
        $vendor = $this->court->venue->owner;
        $venue = $this->court->venue;

        $this->actingAs($vendor)->post(route('vendor.venues.services.store', $venue), [
            'activity_type_id' => $this->court->activity_type_id,
            'name' => 'Court C',
            'slot_minutes' => 30,
            'min_slots' => 2,
            'max_slots' => 6,
            'buffer_minutes' => 5,
            'lead_time_minutes' => 30,
            'is_active' => 1,
            'options' => [
                ['name' => 'Full court', 'price_per_slot' => 2000, 'capacity' => 1],
                ['name' => 'Half court', 'price_per_slot' => 1200, 'capacity' => 2],
            ],
            'default_option' => 0,
            'rates' => [
                ['name' => 'Peak', 'days' => [1, 2], 'starts_at' => '17:00', 'ends_at' => '21:00', 'multiplier' => 1.5],
            ],
        ])->assertRedirect(route('vendor.venues.services.index', $venue));

        $created = Service::where('name', 'Court C')->firstOrFail();
        $this->assertCount(2, $created->options);
        $this->assertTrue($created->options->firstWhere('name', 'Full court')->is_default);
        $this->assertCount(1, $created->rates);
    }

    public function test_admin_panel_renders_and_toggles_venues(): void
    {
        $admin = User::factory()->create(['role_id' => Role::SuperAdministrator]);
        $venue = Venue::firstOrFail();

        $this->actingAs($admin);
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('admin.venues.index'))->assertOk();
        $this->get(route('admin.activity-types.index'))->assertOk()->assertSee('Paintball');
        $this->get(route('admin.activity-types.create'))->assertOk();
        $this->get(route('admin.games.index'))->assertOk()->assertSee('EA Sports FC 26');

        $this->post(route('admin.venues.approval', $venue))->assertRedirect();
        $this->assertFalse($venue->fresh()->is_approved);
        $this->get(route('venues.show', $venue))->assertOk(); // admin can still see it
        $this->actingAs($this->customer)->get(route('venues.show', $venue))->assertNotFound();
    }

    public function test_registration_assigns_roles(): void
    {
        $this->post('/register', ['name' => 'New Vendor', 'email' => 'nv@example.com', 'phone' => '0712223334', 'password' => 'password123', 'password_confirmation' => 'password123', 'account_type' => 'vendor'])
            ->assertRedirect(route('vendor.venues.create'));
        $this->assertSame(Role::Vendor, User::where('email', 'nv@example.com')->first()->role_id);
    }

    protected function draft(Service $service, string $startsAt, int $slots): array
    {
        return [
            'service_id' => $service->id,
            'option_id' => $service->defaultOption()->id,
            'game_id' => null,
            'starts_at' => $startsAt,
            'slots' => $slots,
            'players' => null,
        ];
    }

    protected function details(): array
    {
        return ['customer_name' => 'Test Customer', 'customer_phone' => '0771234567'];
    }
}
