<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Filament\Vendor\Pages\CheckIn;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Database\Seeders\ActivityTypeSeeder;
use Database\Seeders\VenueSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingCheckinTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected Service $court;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-14 09:00:00'); // a Monday

        $this->seed([ActivityTypeSeeder::class, VenueSeeder::class]);
        $this->customer = User::factory()->create(['role_id' => Role::Customer, 'phone' => '0771111111']);
        $this->court = Service::where('name', 'Court A (Main)')->firstOrFail();
        Filament::setCurrentPanel('vendor');
    }

    public function test_a_new_booking_gets_a_unique_non_null_qr_token(): void
    {
        $booking = $this->makeBooking();

        $this->assertNotNull($booking->qr_token);
        $this->assertSame(1, Booking::where('qr_token', $booking->qr_token)->count());

        $other = $this->makeBooking(Carbon::parse('2026-09-16 10:00:00'));
        $this->assertNotSame($booking->qr_token, $other->qr_token);
    }

    public function test_owning_vendor_can_view_the_checkin_page_for_the_booking(): void
    {
        $booking = $this->makeBooking();
        $vendor = $this->court->venue->owner;

        $this->actingAs($vendor)->get(route('filament.vendor.pages.check-in', ['code' => $booking->qr_token]))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee($booking->customer_name);
    }

    public function test_owning_vendor_can_check_in_the_booking(): void
    {
        $booking = $this->makeBooking();
        $vendor = $this->court->venue->owner;

        Livewire::actingAs($vendor)->test(CheckIn::class)
            ->set('code', $booking->qr_token)
            ->call('lookup')
            ->assertSet('booking.id', $booking->id)
            ->call('checkIn')
            ->assertNotified();

        $booking->refresh();
        $this->assertNotNull($booking->checked_in_at);
        $this->assertSame($vendor->id, $booking->checked_in_by);
    }

    public function test_checking_in_an_already_checked_in_booking_does_not_error_or_change_the_timestamp(): void
    {
        $booking = $this->makeBooking();
        $vendor = $this->court->venue->owner;

        Livewire::actingAs($vendor)->test(CheckIn::class)
            ->set('code', $booking->qr_token)
            ->call('lookup')
            ->call('checkIn');

        $firstCheckIn = $booking->fresh()->checked_in_at;

        Livewire::actingAs($vendor)->test(CheckIn::class)
            ->set('code', $booking->qr_token)
            ->call('lookup')
            ->call('checkIn')
            ->assertNotified();

        $booking->refresh();
        $this->assertNotNull($booking->checked_in_at);
        $this->assertEquals($firstCheckIn->toDateTimeString(), $booking->checked_in_at->toDateTimeString());
    }

    public function test_a_vendor_from_a_different_venue_cannot_view_or_check_in_the_booking(): void
    {
        $booking = $this->makeBooking();
        $stranger = User::where('email', 'ciel@entrypoint.lk')->firstOrFail();
        $this->assertNotSame($stranger->id, $this->court->venue->user_id);

        // A booking that exists but belongs to another vendor's venue must look identical to one
        // that doesn't exist at all — never revealed by the lookup.
        Livewire::actingAs($stranger)->test(CheckIn::class)
            ->set('code', $booking->qr_token)
            ->call('lookup')
            ->assertSet('booking', null)
            ->assertNotified();

        $this->assertNull($booking->fresh()->checked_in_at);
    }

    public function test_checking_in_a_cancelled_booking_is_rejected_and_does_not_check_it_in(): void
    {
        $booking = $this->makeBooking();
        $vendor = $this->court->venue->owner;
        app(BookingService::class)->cancel($booking, $vendor);

        Livewire::actingAs($vendor)->test(CheckIn::class)
            ->set('code', $booking->qr_token)
            ->call('lookup')
            ->assertSet('booking.id', $booking->id)
            ->call('checkIn')
            ->assertNotified();

        $this->assertNull($booking->fresh()->checked_in_at);
    }

    public function test_lookup_resolves_a_booking_by_its_plain_reference(): void
    {
        $booking = $this->makeBooking();
        $vendor = $this->court->venue->owner;

        Livewire::actingAs($vendor)->test(CheckIn::class)
            ->set('code', $booking->reference)
            ->call('lookup')
            ->assertSet('booking.id', $booking->id);
    }

    public function test_customer_booking_page_shows_the_checkin_qr_code_when_active(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($this->customer)->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertSee($booking->qr_token, false)
            ->assertSee($booking->checkinUrl(), false);
    }

    protected function makeBooking(?Carbon $start = null): Booking
    {
        $service = app(BookingService::class);

        $booking = $service->reserve(
            $this->customer,
            $this->court,
            $this->court->defaultOption(),
            $start ?: Carbon::parse('2026-09-15 10:00:00'),
            1,
            PaymentMethod::BankTransfer,
            [
                'customer_name' => 'Test Customer',
                'customer_phone' => '0771234567',
                'nic_front_path' => 'identity-documents/test/front.jpg',
                'nic_back_path' => 'identity-documents/test/back.jpg',
            ],
        );

        $service->markPaid($booking, $this->court->venue->owner);

        return $booking->fresh();
    }
}
