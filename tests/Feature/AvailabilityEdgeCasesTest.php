<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Exceptions\SlotUnavailableException;
use App\Livewire\BookingBuilder;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
use App\Support\Settings;
use Carbon\Carbon;
use Database\Seeders\ActivityTypeSeeder;
use Database\Seeders\VenueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The booking engine edge cases: gaps between bookings, minimum durations, buffers, closing time,
 * after-midnight venues, interchangeable units, rates at midnight, lead time, hold expiry.
 */
class AvailabilityEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected BookingService $engine;

    protected AvailabilityService $availability;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-14 09:00:00'); // Monday 09:00
        $this->seed([ActivityTypeSeeder::class, VenueSeeder::class]);
        $this->engine = app(BookingService::class);
        $this->availability = app(AvailabilityService::class);
    }

    public function test_duration_is_capped_by_the_next_booking(): void
    {
        $court = $this->service('Rooftop Court'); // 1 unit, 60-min blocks, max 3, no buffer, open 09:00–22:00
        $this->reserve($court, '2026-09-15 12:00:00', 1);

        $this->assertSame(2, $this->availability->maxSlotsFrom($court, $court->defaultOption(), Carbon::parse('2026-09-15 10:00:00')));
        $this->assertSame(0, $this->availability->maxSlotsFrom($court, $court->defaultOption(), Carbon::parse('2026-09-15 12:00:00')));
        $this->assertSame(3, $this->availability->maxSlotsFrom($court, $court->defaultOption(), Carbon::parse('2026-09-15 13:00:00')));

        // Three hours from 10:00 would run into the 12:00 booking; two fit.
        try {
            $this->reserve($court, '2026-09-15 10:00:00', 3);
            $this->fail('Overlapping booking accepted');
        } catch (SlotUnavailableException $e) {
            $this->assertStringContainsString('no longer available', $e->getMessage());
        }
        $this->reserve($court, '2026-09-15 10:00:00', 2);
        $this->assertSame(2, Booking::active()->count());
    }

    public function test_builder_will_not_let_the_customer_exceed_the_gap(): void
    {
        $court = $this->service('Rooftop Court');
        $this->reserve($court, '2026-09-15 12:00:00', 1);
        $this->actingAs(User::factory()->create(['role_id' => Role::Customer]));

        Livewire::test(BookingBuilder::class, ['service' => $court])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '2026-09-15 10:00:00')
            ->call('incrementSlots')
            ->call('incrementSlots')
            ->call('incrementSlots')
            ->assertSet('blocks', 2) // stepper stops at the gap
            ->assertSee('Another booking (or closing time) follows this slot')
            ->set('blocks', 3)        // even a tampered value is rejected
            ->call('checkout')
            ->assertSet('showCheckout', false)
            ->assertSet('error', 'You can book between 1 and 2 blocks from this start time.');
    }

    public function test_start_times_that_cannot_fit_the_minimum_duration_are_not_bookable(): void
    {
        $nets = $this->service('Cricket Nets', 'Galle Fort Turf'); // 30-min blocks, minimum 2 blocks, 2 nets
        // Fill both nets 10:30–11:00 so a booking starting at 10:00 can only ever be 30 minutes long.
        $this->reserve($nets, '2026-09-15 10:30:00', 2);
        $this->reserve($nets, '2026-09-15 10:30:00', 2);

        $slots = $this->availability->slotsForDay($nets, $nets->defaultOption(), Carbon::parse('2026-09-15'))->keyBy(fn ($s) => $s['start']->format('H:i'));

        $this->assertTrue($slots['10:00']['available'], '10:00 itself is free');
        $this->assertFalse($slots['10:00']['bookable'], 'but nothing of the minimum length can start there');
        $this->assertSame(1, $slots['10:00']['max_blocks']);
        $this->assertFalse($slots['10:30']['available']);
        $this->assertTrue($slots['11:30']['bookable']);

        $this->expectException(SlotUnavailableException::class);
        $this->reserve($nets, '2026-09-15 10:00:00', 2);
    }

    public function test_identical_units_are_interchangeable_so_capacity_is_checked_per_block(): void
    {
        $courts = $this->service('Hard Courts'); // 2 courts, 60-min blocks, max 2
        $this->reserve($courts, '2026-09-15 10:00:00', 1); // court X 10–11
        $this->reserve($courts, '2026-09-15 11:00:00', 1); // court Y 11–12

        // A 10–12 booking fits on the other court in each hour.
        $long = $this->reserve($courts, '2026-09-15 10:00:00', 2);
        $this->assertSame(BookingStatus::Pending, $long->status);
        $this->assertSame(3, Booking::active()->count());

        // Now both courts are busy in both hours.
        $this->expectException(SlotUnavailableException::class);
        $this->reserve($courts, '2026-09-15 10:00:00', 2);
    }

    public function test_bank_transfer_bumps_only_as_many_holds_as_it_needs(): void
    {
        $courts = $this->service('Hard Courts'); // 2 courts
        $first = $this->reserve($courts, '2026-09-15 10:00:00', 1);
        Carbon::setTestNow('2026-09-14 09:05:00');
        $second = $this->reserve($courts, '2026-09-15 10:00:00', 1);

        $paid = $this->reserve($courts, '2026-09-15 10:00:00', 1, method: PaymentMethod::BankTransfer);

        $this->assertSame(BookingStatus::Pending, $first->fresh()->status, 'oldest hold survives');
        $this->assertSame(BookingStatus::Bumped, $second->fresh()->status, 'most recent hold is replaced');
        $this->assertSame($paid->id, $second->fresh()->bumped_by_booking_id);
    }

    public function test_venues_closing_after_midnight_keep_late_slots_on_the_right_day(): void
    {
        $ps5 = $this->service('PS5 Stations'); // Club Fusion: 10:00 – 02:00
        $option = $ps5->defaultOption();
        $game = $ps5->games->first();

        $slots = $this->availability->slotsForDay($ps5, $option, Carbon::parse('2026-09-15'));
        $this->assertSame('2026-09-15 10:00:00', $slots->first()['start']->toDateTimeString());
        $this->assertSame('2026-09-16 01:00:00', $slots->last()['start']->toDateTimeString());
        $this->assertSame(16, $slots->count());

        $this->assertSame('2026-09-15', $this->availability->sessionDayFor($ps5, Carbon::parse('2026-09-16 01:00:00'))->toDateString());
        $this->assertNull($this->availability->sessionDayFor($ps5, Carbon::parse('2026-09-16 03:00:00')));

        // 01:00–02:00 on the 16th belongs to the 15th's session and is bookable…
        $late = $this->reserve($ps5, '2026-09-16 01:00:00', 1, game: $game);
        $this->assertSame('2026-09-16 01:00:00', $late->starts_at->toDateTimeString());

        // …but two hours from 01:00 would run past 02:00 closing.
        try {
            $this->reserve($ps5, '2026-09-16 01:00:00', 2, game: $game);
            $this->fail('Should not be able to book past closing');
        } catch (SlotUnavailableException $e) {
            $this->assertStringContainsString('closing time', $e->getMessage());
        }

        // The builder sends the full datetime, so the summary and the booking carry the right date.
        $this->actingAs(User::factory()->create(['role_id' => Role::Customer]));
        Livewire::test(BookingBuilder::class, ['service' => $ps5])
            ->call('selectDate', '2026-09-15')
            ->assertSee('01:00 AM')
            ->set('gameId', $game->id)
            ->call('selectTime', '2026-09-16 00:00:00')
            ->assertSee('Tue, 15 Sep 2026')
            ->assertSee('Wed 16 Sep · 12:00 AM')
            ->call('checkout')
            ->assertSet('showCheckout', true)
            ->set('customerName', 'Night Owl')
            ->set('customerPhone', '0771234567')
            ->call('placeBooking')
            ->assertHasNoErrors();

        $this->assertSame('2026-09-16 00:00:00', Booking::latest('id')->first()->starts_at->toDateTimeString());
    }

    public function test_peak_rates_that_end_at_midnight_apply_to_the_late_evening(): void
    {
        $ps5 = $this->service('PS5 Stations'); // Weekend peak Fri/Sat/Sun 18:00–00:00 ×1.3 on a Rs 500 seat
        $quote = app(PricingService::class)->quote($ps5, $ps5->defaultOption(), Carbon::parse('2026-09-19 22:00:00'), 2); // Saturday 22:00–00:00

        $this->assertEquals(1300, $quote['total']);
        $this->assertSame('Weekend peak', $quote['lines'][1]['label']);

        // Just after midnight is outside the window; a Monday evening is not a weekend.
        $this->assertEquals(500, app(PricingService::class)->quote($ps5, $ps5->defaultOption(), Carbon::parse('2026-09-20 00:00:00'), 1)['total']);
        $this->assertEquals(500, app(PricingService::class)->quote($ps5, $ps5->defaultOption(), Carbon::parse('2026-09-14 20:00:00'), 1)['total']);
    }

    public function test_lead_time_and_booking_window_are_enforced(): void
    {
        $court = $this->service('Court A (Main)'); // 60-minute notice
        $slots = $this->availability->slotsForDay($court, $court->defaultOption(), today())->keyBy(fn ($s) => $s['start']->format('H:i'));
        $this->assertFalse($slots['09:00']['available'], 'inside the notice period');
        $this->assertTrue($slots['10:00']['available']);

        try {
            $this->reserve($court, '2026-09-14 09:00:00', 1);
            $this->fail('Inside lead time');
        } catch (SlotUnavailableException $e) {
            $this->assertStringContainsString('in advance', $e->getMessage());
        }

        try {
            $this->reserve($court, '2026-09-29 10:00:00', 1); // 15 days ahead, window is 14
            $this->fail('Beyond booking window');
        } catch (SlotUnavailableException $e) {
            $this->assertStringContainsString('14 days', $e->getMessage());
        }

        $this->assertNotNull($this->reserve($court, '2026-09-28 10:00:00', 1));
    }

    public function test_bookings_cannot_run_past_closing_time(): void
    {
        $court = $this->service('Court A (Main)'); // 06:00 – 00:00, max 4 blocks
        $this->assertSame(2, $this->availability->maxSlotsFrom($court, $court->defaultOption(), Carbon::parse('2026-09-15 22:00:00')));
        $this->assertSame(4, $this->availability->maxSlotsFrom($court, $court->defaultOption(), Carbon::parse('2026-09-15 10:00:00')));

        $this->expectException(SlotUnavailableException::class);
        $this->expectExceptionMessageMatches('/closing time/');
        $this->reserve($court, '2026-09-15 22:00:00', 3);
    }

    public function test_buffer_keeps_a_gap_around_bookings(): void
    {
        $court = $this->service('Court A (Main)'); // 10-minute buffer on 60-minute blocks
        $this->reserve($court, '2026-09-15 12:00:00', 1);

        $slots = $this->availability->slotsForDay($court, $court->defaultOption(), Carbon::parse('2026-09-15'))->keyBy(fn ($s) => $s['start']->format('H:i'));
        $this->assertTrue($slots['10:00']['available']);
        $this->assertFalse($slots['11:00']['available'], 'would end 10 minutes too close to the 12:00 booking');
        $this->assertFalse($slots['12:00']['available']);
        $this->assertFalse($slots['13:00']['available'], 'would start 10 minutes too soon after it');
        $this->assertTrue($slots['14:00']['available']);
        $this->assertSame(1, $slots['10:00']['max_blocks']);
    }

    public function test_unverified_bank_transfers_expire_and_free_the_slot(): void
    {
        $court = $this->service('Rooftop Court');
        $hold = $this->reserve($court, '2026-09-15 12:00:00', 1, method: PaymentMethod::BankTransfer);
        $customer = $hold->user;
        $this->assertSame('2026-09-14 09:20:00', $hold->hold_expires_at->toDateTimeString());

        // Still holding at 19 minutes.
        Carbon::setTestNow('2026-09-14 09:19:00');
        $this->assertFalse($this->availability->slotsForDay($court, $court->defaultOption(), Carbon::parse('2026-09-15'))->firstWhere('label', '12:00 PM')['available']);
        $this->assertSame(0, $this->engine->expireStaleHolds());

        // At 21 minutes the slot is free again even before the sweeper runs…
        Carbon::setTestNow('2026-09-14 09:21:00');
        $this->assertTrue($this->availability->slotsForDay($court, $court->defaultOption(), Carbon::parse('2026-09-15'))->firstWhere('label', '12:00 PM')['available']);

        // …and the sweeper marks it expired and tells both sides.
        $this->artisan('bookings:expire-holds')->expectsOutputToContain('Expired 1')->assertSuccessful();
        $hold->refresh();
        $this->assertSame(BookingStatus::Expired, $hold->status);
        $this->assertSame('Bank transfer was not verified in time', $hold->cancel_reason);
        $this->assertSame(PaymentStatus::Unpaid, $hold->payments->last()->status);
        $this->assertTrue($customer->fresh()->notifications->pluck('data.message')->contains(fn ($m) => str_contains($m, 'expired')));
        $this->assertTrue($court->venue->owner->fresh()->notifications->pluck('data.message')->contains(fn ($m) => str_contains($m, 'expired unverified')));

        // Someone else can now take it.
        $again = $this->reserve($court, '2026-09-15 12:00:00', 1);
        $this->assertSame(BookingStatus::Pending, $again->status);

        $this->actingAs($customer)->get(route('bookings.show', $hold))->assertOk()->assertSee('This booking expired');
    }

    public function test_verifying_or_confirming_a_transfer_stops_the_clock(): void
    {
        $court = $this->service('Rooftop Court');
        $verified = $this->reserve($court, '2026-09-15 12:00:00', 1, method: PaymentMethod::BankTransfer);
        $confirmed = $this->reserve($court, '2026-09-15 14:00:00', 1, method: PaymentMethod::BankTransfer);

        $this->engine->markPaid($verified, $court->venue->owner, 'TXN1');
        $this->engine->vendorConfirm($confirmed);

        Carbon::setTestNow('2026-09-14 10:00:00');
        $this->assertSame(0, $this->engine->expireStaleHolds());
        $this->assertSame(BookingStatus::Confirmed, $verified->fresh()->status);
        $this->assertSame(BookingStatus::Confirmed, $confirmed->fresh()->status);
        $this->assertNull($confirmed->fresh()->hold_expires_at);
    }

    public function test_hold_window_length_comes_from_site_settings(): void
    {
        Settings::set('payments.bank_transfer_hold_minutes', 45);
        $hold = $this->reserve($this->service('Rooftop Court'), '2026-09-15 12:00:00', 1, method: PaymentMethod::BankTransfer);
        $this->assertSame('2026-09-14 09:45:00', $hold->hold_expires_at->toDateTimeString());
    }

    public function test_payment_methods_can_be_switched_off_by_the_super_admin(): void
    {
        Settings::set('payments.enabled', ['pay_at_venue']);
        $this->assertFalse(PaymentMethod::BankTransfer->isAvailable());
        $this->assertSame('Temporarily off', PaymentMethod::BankTransfer->availabilityLabel());
        $this->assertSame('Coming soon', PaymentMethod::Card->availabilityLabel());

        try {
            $this->reserve($this->service('Rooftop Court'), '2026-09-15 12:00:00', 1, method: PaymentMethod::BankTransfer);
            $this->fail('Disabled method accepted');
        } catch (SlotUnavailableException $e) {
            $this->assertStringContainsString('not available', $e->getMessage());
        }

        $this->actingAs(User::factory()->create(['role_id' => Role::Customer]));
        Livewire::test(BookingBuilder::class, ['service' => $this->service('Rooftop Court')])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '2026-09-15 12:00:00')
            ->call('checkout')
            ->assertSee('Temporarily off')
            ->call('selectPaymentMethod', 'bank_transfer')
            ->assertSet('paymentMethod', 'pay_at_venue');

        // Gateways can never be enabled from settings until they are integrated.
        Settings::set('payments.enabled', ['pay_at_venue', 'card']);
        $this->assertFalse(PaymentMethod::Card->isAvailable());
    }

    public function test_a_selected_start_time_that_gets_taken_is_dropped_on_refresh(): void
    {
        $court = $this->service('Rooftop Court');
        $this->actingAs(User::factory()->create(['role_id' => Role::Customer]));

        $builder = Livewire::test(BookingBuilder::class, ['service' => $court])
            ->call('selectDate', '2026-09-15')
            ->call('selectTime', '2026-09-15 12:00:00')
            ->assertSet('startAt', '2026-09-15 12:00:00');

        $this->reserve($court, '2026-09-15 12:00:00', 1); // someone else grabs it

        $builder->call('$refresh')
            ->assertSet('startAt', null)
            ->call('checkout')
            ->assertSet('error', 'Pick a start time to continue.');
    }

    public function test_option_must_belong_to_the_service(): void
    {
        $court = $this->service('Rooftop Court');
        $foreign = $this->service('Court A (Main)')->defaultOption();

        $this->expectException(SlotUnavailableException::class);
        $this->engine->reserve($this->customer(), $court, $foreign, Carbon::parse('2026-09-15 12:00:00'), 1, PaymentMethod::PayAtVenue, $this->details());
    }

    public function test_closed_days_and_inactive_services_are_rejected(): void
    {
        $court = $this->service('Rooftop Court');
        $court->venue->hours()->where('day_of_week', 2)->update(['is_closed' => true]); // Tuesdays closed
        $court->venue->load('hours');

        try {
            $this->reserve($court, '2026-09-15 12:00:00', 1);
            $this->fail('Closed day accepted');
        } catch (SlotUnavailableException $e) {
            $this->assertStringContainsString('closed', $e->getMessage());
        }

        $court->update(['is_active' => false]);
        $this->expectException(SlotUnavailableException::class);
        $this->reserve($court->fresh(), '2026-09-16 12:00:00', 1);
    }

    // ---------------------------------------------------------------------------------------------

    protected function service(string $name, ?string $venue = null): Service
    {
        return Service::with(['venue.hours', 'venue.owner', 'options', 'rates', 'games', 'activityType'])
            ->where('name', $name)
            ->when($venue, fn ($q) => $q->whereHas('venue', fn ($v) => $v->where('name', $venue)))
            ->firstOrFail();
    }

    protected function reserve(Service $service, string $start, int $slots, PaymentMethod $method = PaymentMethod::PayAtVenue, ?User $user = null, $game = null): Booking
    {
        return $this->engine->reserve($user ?? $this->customer(), $service, $service->defaultOption(), Carbon::parse($start), $slots, $method, $this->details(), $game);
    }

    protected function customer(): User
    {
        return User::factory()->create(['role_id' => Role::Customer, 'phone' => '0771111111']);
    }

    protected function details(): array
    {
        return ['customer_name' => 'Edge Case', 'customer_phone' => '0771234567'];
    }
}
