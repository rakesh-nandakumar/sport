<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Exceptions\SlotUnavailableException;
use App\Filament\Resources\Venues\Pages\ListVenues;
use App\Filament\Vendor\Resources\Bookings\Pages\ListBookings;
use App\Filament\Vendor\Resources\Venues\Pages\EditVenue;
use App\Filament\Vendor\Resources\Venues\RelationManagers\ServicesRelationManager;
use App\Livewire\BookingBuilder;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Venue;
use App\Services\BookingService;
use Carbon\Carbon;
use Database\Seeders\ActivityTypeSeeder;
use Database\Seeders\VenueSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $this->get('/')->assertOk()->assertSee('Book any sport')->assertSee('/images/activities/futsal.jpg');
        $this->get('/venues?activity=futsal')->assertOk()->assertSee('CR7 Futsal Arena');
        $this->get('/venues/cr7-futsal-arena')->assertOk()->assertSee('Court A (Main)')->assertSee('Evening peak')->assertSee('start times open today');
        $this->get(route('booking.build', $this->court))->assertOk()->assertSeeLivewire(BookingBuilder::class);
    }

    public function test_checkout_button_opens_the_payment_modal_with_every_method(): void
    {
        $this->actingAs($this->customer);

        Livewire::test(BookingBuilder::class, ['service' => $this->court])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '2026-09-15 18:00:00')
            ->call('incrementSlots')
            ->assertSee('2 hr')
            ->assertSee('Evening peak')
            ->assertSet('showCheckout', false)
            ->call('checkout')
            ->assertSet('showCheckout', true)
            ->assertSet('paymentMethod', 'pay_at_venue')
            ->assertSee('Complete your booking')
            ->assertSee('Pay at Venue')
            ->assertSee('Bank Transfer')
            ->assertSee('Debit / Credit Card')
            ->assertSee('Koko')
            ->assertSee('Mint Pay')
            ->assertSee('PayEasy')
            ->assertSee('Coming soon')
            ->assertSee('Rs 12,500'); // 2 × 5,000 + 25% peak on both blocks
    }

    public function test_placing_a_pay_at_venue_booking_from_the_modal(): void
    {
        $this->actingAs($this->customer);

        Livewire::test(BookingBuilder::class, ['service' => $this->court])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '2026-09-15 10:00:00')
            ->call('checkout')
            ->set('customerName', 'Sahan J')
            ->set('customerPhone', '0771234567')
            ->set('notes', 'Bibs please')
            ->call('beginPayAtVenueConfirmation')
            ->assertSet('showPayAtVenueWarning', true)
            ->assertSee('NIC verification is required')
            ->set('nicFront', UploadedFile::fake()->image('nic-front.jpg'))
            ->set('nicBack', UploadedFile::fake()->image('nic-back.jpg'))
            ->call('confirmPayAtVenueBooking')
            ->assertHasNoErrors()
            ->assertRedirect(route('bookings.show', Booking::firstOrFail()));

        $booking = Booking::firstOrFail();
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame(PaymentStatus::Unpaid, $booking->payment_status);
        $this->assertSame(1, $booking->priority);
        $this->assertNull($booking->hold_expires_at);
        $this->assertEquals(5000, $booking->total);
        $this->assertSame('Bibs please', $booking->notes);
        $this->assertNotNull($booking->nic_front_path);
        $this->assertNotNull($booking->nic_back_path);
        $this->assertNull($booking->qr_token);

        $this->get(route('bookings.show', $booking))->assertOk()->assertSee($booking->reference)->assertSee('pay');
        $this->assertCount(1, $this->customer->notifications);
        $this->assertCount(1, $this->court->venue->owner->notifications);
    }

    public function test_bank_transfer_from_the_modal_takes_reference_and_slip_and_starts_the_hold_timer(): void
    {
        Storage::fake('local');
        $this->actingAs($this->customer);

        Livewire::test(BookingBuilder::class, ['service' => $this->court])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '2026-09-15 10:00:00')
            ->call('checkout')
            ->call('selectPaymentMethod', 'bank_transfer')
            ->assertSee('Commercial Bank')
            ->assertSee('20-minute hold')
            ->set('bankReference', 'TXN-777')
            ->set('proof', UploadedFile::fake()->image('slip.jpg'))
            ->call('placeBooking')
            ->assertHasNoErrors();

        $booking = Booking::firstOrFail();
        $this->assertSame(PaymentMethod::BankTransfer, $booking->payment_method);
        $this->assertSame(PaymentStatus::PendingVerification, $booking->payment_status);
        $this->assertSame(2, $booking->priority);
        $this->assertEquals(now()->addMinutes(20)->toDateTimeString(), $booking->hold_expires_at->toDateTimeString());
        $payment = $booking->payments->last();
        $this->assertSame('TXN-777', $payment->reference);
        Storage::disk('local')->assertExists($payment->proof_path);

        // The slip is private: customer and venue owner can open it, a stranger cannot.
        $this->get(route('bookings.proof.show', [$booking, $payment]))->assertOk();
        $this->actingAs($this->court->venue->owner)->get(route('bookings.proof.show', [$booking, $payment]))->assertOk();
        $this->actingAs(User::factory()->create(['role_id' => Role::Customer]))->get(route('bookings.proof.show', [$booking, $payment]))->assertForbidden();

        $this->actingAs($this->customer)->get(route('bookings.show', $booking))->assertOk()->assertSee('Slot held for')->assertSee('TXN-777');
    }

    public function test_builder_requires_a_game_for_gaming_services(): void
    {
        $this->actingAs($this->customer);

        Livewire::test(BookingBuilder::class, ['service' => $this->ps5])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '2026-09-15 14:00:00')
            ->call('checkout')
            ->assertSet('error', 'Choose the game you want to play.')
            ->assertSet('showCheckout', false)
            ->set('gameId', $this->ps5->games->first()->id)
            ->call('checkout')
            ->assertSet('showCheckout', true);
    }

    public function test_guest_is_sent_to_login_from_checkout(): void
    {
        Livewire::test(BookingBuilder::class, ['service' => $this->court])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '2026-09-15 10:00:00')
            ->call('checkout')
            ->assertRedirect(route('login'));
    }

    public function test_unavailable_gateways_are_rejected_even_if_forced(): void
    {
        $this->actingAs($this->customer);

        Livewire::test(BookingBuilder::class, ['service' => $this->court])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '2026-09-15 10:00:00')
            ->call('checkout')
            ->call('selectPaymentMethod', 'card')
            ->assertSet('paymentMethod', 'pay_at_venue') // ignored: not available
            ->set('paymentMethod', 'card')
            ->call('placeBooking')
            ->assertHasErrors('paymentMethod');

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

        $winner = $service->reserve($other, $this->court, $this->court->defaultOption(), $start, 1, PaymentMethod::BankTransfer, $this->details());

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

    public function test_repeated_pay_at_venue_failures_restrict_the_method_until_an_admin_restores_it(): void
    {
        $service = app(BookingService::class);

        for ($i = 0; $i < 3; $i++) {
            $booking = $service->reserve(
                $this->customer,
                $this->court,
                $this->court->defaultOption(),
                Carbon::parse('2026-09-'.(16 + $i).' 10:00:00'),
                1,
                PaymentMethod::PayAtVenue,
                $this->details(),
            );
            $service->cancel($booking, $this->customer, 'Cannot attend');
        }

        $this->assertTrue($this->customer->fresh()->isPayAtVenueBanned());
        $this->expectException(SlotUnavailableException::class);
        $service->reserve($this->customer, $this->court, $this->court->defaultOption(), Carbon::parse('2026-09-20 10:00:00'), 1, PaymentMethod::PayAtVenue, $this->details());
    }

    public function test_vendor_panel_manages_bookings(): void
    {
        Filament::setCurrentPanel('vendor');
        $service = app(BookingService::class);
        $booking = $service->reserve($this->customer, $this->court, $this->court->defaultOption(), Carbon::parse('2026-09-16 10:00:00'), 1, PaymentMethod::BankTransfer, $this->details());
        $vendor = $this->court->venue->owner;

        $this->actingAs($vendor);
        $this->get(route('filament.vendor.pages.dashboard'))->assertOk()->assertSee('CR7 Futsal Arena')->assertSee('waiting for your verification')->assertSee($booking->reference);
        $this->get(route('filament.vendor.resources.venues.index'))->assertOk();
        $this->get(route('filament.vendor.resources.venues.edit', $this->court->venue))->assertOk();
        $this->get(route('filament.vendor.resources.bookings.index', ['tableFilters[pending_payment][value]' => true]))->assertOk()->assertSee($booking->reference);
        $this->get(route('filament.vendor.resources.bookings.view', $booking))->assertOk()->assertSee('Verify within');

        Livewire::actingAs($vendor)->test(ListBookings::class)
            ->callTableAction('markPaid', $booking, data: ['reference' => 'TXN999'])
            ->assertHasNoTableActionErrors();

        $booking->refresh();
        $this->assertSame(PaymentStatus::Paid, $booking->payment_status);
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(3, $booking->priority);
        $this->assertNull($booking->hold_expires_at);

        $this->actingAs($this->customer)->get(route('filament.vendor.pages.dashboard'))->assertForbidden();
    }

    public function test_vendor_can_create_a_service_with_options_and_rates(): void
    {
        Filament::setCurrentPanel('vendor');
        $vendor = $this->court->venue->owner;
        $venue = $this->court->venue;

        Livewire::actingAs($vendor)->test(ServicesRelationManager::class, ['ownerRecord' => $venue, 'pageClass' => EditVenue::class])
            ->callTableAction('create', data: [
                'activity_type_id' => $this->court->activity_type_id,
                'name' => 'Court C',
                'slot_minutes' => 30,
                'min_slots' => 2,
                'max_slots' => 6,
                'buffer_minutes' => 5,
                'lead_time_minutes' => 30,
                'is_active' => 1,
                'options' => [
                    ['name' => 'Full court', 'price_per_slot' => 2000, 'capacity' => 1, 'is_default' => true],
                    ['name' => 'Half court', 'price_per_slot' => 1200, 'capacity' => 2, 'is_default' => false],
                ],
                'rates' => [
                    ['name' => 'Peak', 'days' => [1, 2], 'starts_at' => '17:00', 'ends_at' => '21:00', 'multiplier' => 1.5],
                ],
            ])
            ->assertHasNoTableActionErrors();

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
        $this->get(route('filament.admin.pages.dashboard'))->assertOk();
        $this->get(route('filament.admin.resources.users.index'))->assertOk();
        $this->get(route('filament.admin.resources.venues.index'))->assertOk();
        $this->get(route('filament.admin.resources.activity-types.index'))->assertOk()->assertSee('Paintball');
        $this->get(route('filament.admin.resources.activity-types.create'))->assertOk()->assertSee('Tile photo');
        $this->get(route('filament.admin.resources.games.index'))->assertOk()->assertSee('EA Sports FC 26');

        Livewire::actingAs($admin)->test(ListVenues::class)
            ->callTableAction('toggleApproval', $venue)
            ->assertHasNoTableActionErrors();

        $this->assertFalse($venue->fresh()->is_approved);
        $this->actingAs($admin)->get(route('venues.show', $venue))->assertOk(); // admin can still see it
        $this->actingAs($this->customer)->get(route('venues.show', $venue))->assertNotFound();
    }

    public function test_customer_registration(): void
    {
        $this->post('/register', ['name' => 'New Customer', 'email' => 'nc@example.com', 'phone' => '0712223334', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertRedirect('/');
        $this->assertSame(Role::Customer, User::where('email', 'nc@example.com')->first()->role_id);
    }

    protected function details(): array
    {
        return [
            'customer_name' => 'Test Customer',
            'customer_phone' => '0771234567',
            // Service-level reservations enforce the same NIC requirement as the checkout modal.
            'nic_front_path' => 'identity-documents/test/front.jpg',
            'nic_back_path' => 'identity-documents/test/back.jpg',
        ];
    }
}
