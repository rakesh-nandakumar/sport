<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Exceptions\SlotUnavailableException;
use App\Livewire\BookingBuilder;
use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Database\Seeders\ActivityTypeSeeder;
use Database\Seeders\VenueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class VenueOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected Service $ps5;

    protected Service $pc;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-14 09:00:00');
        $this->seed([ActivityTypeSeeder::class, VenueSeeder::class]);

        $this->customer = User::factory()->create(['role_id' => Role::Customer, 'phone' => '0771111111']);
        $this->ps5 = Service::with(['venue.owner', 'venue.hours', 'activityType', 'options', 'games', 'rates'])
            ->where('name', 'PS5 Stations')->firstOrFail();
        $this->pc = Service::with(['venue.owner', 'venue.hours', 'activityType', 'options', 'games', 'rates'])
            ->where('name', 'Esports PC Row')->firstOrFail();
    }

    public function test_several_activities_at_one_venue_are_reserved_as_one_payment_order(): void
    {
        $bookings = app(BookingService::class)->reserveMany($this->customer, [
            [
                'service' => $this->ps5,
                'option' => $this->ps5->defaultOption(),
                'start' => Carbon::parse('2026-09-15 15:00:00'),
                'slots' => 3,
                'games' => $this->ps5->games->take(4),
            ],
            [
                'service' => $this->pc,
                'option' => $this->pc->defaultOption(),
                'start' => Carbon::parse('2026-09-15 15:00:00'),
                'slots' => 3,
                'games' => $this->pc->games->take(2),
            ],
        ], PaymentMethod::BankTransfer, $this->details());

        $this->assertCount(2, $bookings);
        $this->assertSame($bookings->first()->booking_order_id, $bookings->last()->booking_order_id);
        $this->assertSame(4, $bookings->first()->games()->count());

        $order = BookingOrder::with(['bookings', 'payments'])->firstOrFail();
        $this->assertEquals($bookings->sum('total'), $order->total);
        $this->assertCount(1, $order->payments);
        $this->assertEquals($order->total, $order->payments->first()->amount);
        $this->assertSame(PaymentStatus::PendingVerification, $order->payment_status);

        app(BookingService::class)->markPaid($bookings->last(), $this->ps5->venue->owner, 'ORDER-123');

        $this->assertSame(2, Booking::where('booking_order_id', $order->id)->where('status', BookingStatus::Confirmed)->count());
        $this->assertSame(2, Booking::where('booking_order_id', $order->id)->where('payment_status', PaymentStatus::Paid)->count());
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
    }

    public function test_an_order_rolls_back_every_activity_when_one_service_is_unavailable(): void
    {
        $vr = Service::with(['venue.owner', 'venue.hours', 'activityType', 'options', 'games', 'rates'])
            ->where('name', 'VR Corner')->firstOrFail();
        $engine = app(BookingService::class);

        foreach (range(1, $vr->defaultOption()->capacity) as $unused) {
            $engine->reserve(
                $this->customer,
                $vr,
                $vr->defaultOption(),
                Carbon::parse('2026-09-15 15:00:00'),
                1,
                PaymentMethod::PayAtVenue,
                $this->details(),
                $vr->games->take(1),
            );
        }
        $bookingsBefore = Booking::count();
        $ordersBefore = BookingOrder::count();

        try {
            $engine->reserveMany($this->customer, [
                [
                    'service' => $this->ps5,
                    'option' => $this->ps5->defaultOption(),
                    'start' => Carbon::parse('2026-09-15 15:00:00'),
                    'slots' => 1,
                    'games' => $this->ps5->games->take(1),
                ],
                [
                    'service' => $vr,
                    'option' => $vr->defaultOption(),
                    'start' => Carbon::parse('2026-09-15 15:00:00'),
                    'slots' => 1,
                    'games' => $vr->games->take(1),
                ],
            ], PaymentMethod::PayAtVenue, $this->details());
            $this->fail('The unavailable activity was accepted.');
        } catch (SlotUnavailableException $exception) {
            $this->assertStringContainsString('no longer available', $exception->getMessage());
        }

        $this->assertSame($bookingsBefore, Booking::count());
        $this->assertSame($ordersBefore, BookingOrder::count());
    }

    public function test_game_selections_are_limited_by_session_duration_and_saved_to_a_venue_order(): void
    {
        $this->actingAs($this->customer);
        $games = $this->ps5->games->take(4);

        $builder = Livewire::test(BookingBuilder::class, ['service' => $this->ps5])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '2026-09-15 15:00:00');

        foreach ($games as $game) {
            $builder->call('toggleGame', $game->id);
        }

        $builder->call('checkout')
            ->assertSet('showCheckout', false)
            ->assertSee('This session can include up to 1 game');

        $builder->call('incrementSlots')
            ->call('incrementSlots')
            ->call('addToVenueOrder')
            ->assertSee('Venue order (1)');

        Livewire::test(BookingBuilder::class, ['service' => $this->pc])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '2026-09-15 15:00:00')
            ->call('toggleGame', $this->pc->games->first()->id)
            ->call('addToVenueOrder')
            ->call('checkoutVenueOrder')
            ->assertSet('checkoutMode', 'order')
            ->assertSet('showCheckout', true)
            ->assertSee('Complete your venue order')
            ->assertSee('2 activities');
    }

    public function test_pay_at_venue_order_is_placed_once_with_a_qr_for_each_activity(): void
    {
        $this->actingAs($this->customer);

        Livewire::test(BookingBuilder::class, ['service' => $this->ps5])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '2026-09-15 15:00:00')
            ->call('toggleGame', $this->ps5->games->first()->id)
            ->call('addToVenueOrder');

        Livewire::test(BookingBuilder::class, ['service' => $this->pc])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '2026-09-15 15:00:00')
            ->call('toggleGame', $this->pc->games->first()->id)
            ->call('addToVenueOrder')
            ->call('checkoutVenueOrder')
            ->set('customerName', 'Venue Order Customer')
            ->set('customerPhone', '0771234567')
            ->call('beginPayAtVenueConfirmation')
            ->set('nicFront', UploadedFile::fake()->image('nic-front.jpg'))
            ->set('nicBack', UploadedFile::fake()->image('nic-back.jpg'))
            ->call('confirmPayAtVenueBooking')
            ->assertHasNoErrors();

        $order = BookingOrder::with('bookings')->firstOrFail();
        $this->assertCount(2, $order->bookings);
        $this->assertEquals($order->bookings->sum('total'), $order->total);
        $this->assertTrue($order->bookings->every(fn (Booking $booking) => filled($booking->qr_token)));
        $this->assertTrue($order->bookings->every(fn (Booking $booking) => $booking->payment_method === PaymentMethod::PayAtVenue));

        $this->get(route('bookings.show', $order->bookings->first()))
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee('one payment');
    }

    protected function details(): array
    {
        return [
            'customer_name' => 'Venue Order Customer',
            'customer_phone' => '0771234567',
            'nic_front_path' => 'identity-documents/test/front.jpg',
            'nic_back_path' => 'identity-documents/test/back.jpg',
        ];
    }
}
