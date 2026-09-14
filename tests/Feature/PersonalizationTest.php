<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueView;
use App\Services\PersonalizationService;
use Carbon\Carbon;
use Database\Seeders\ActivityTypeSeeder;
use Database\Seeders\VenueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-14 09:00:00');
        User::factory()->create(['role_id' => Role::SuperAdministrator]);
        $this->seed([ActivityTypeSeeder::class, VenueSeeder::class]);
    }

    public function test_guests_are_asked_for_their_location_and_can_store_it(): void
    {
        $this->get('/')->assertOk()->assertSee('data-has-location="0"', false)->assertSee('epRequestLocation')->assertSee('Use my location');

        // Browser geolocation posts coordinates (Kandy) → nearest district label is derived.
        $this->postJson(route('location.store'), ['lat' => 7.29, 'lng' => 80.63])
            ->assertOk()->assertJsonPath('label', 'Kandy');

        $this->get('/')->assertOk()
            ->assertSee('data-has-location="1"', false)
            ->assertSee('Near Kandy')
            ->assertSee('Level Up eSports Hub')
            ->assertSee('km away');

        // A picked district works too, and the label is the district.
        $this->post(route('location.store'), ['district' => 'Galle'])->assertRedirect();
        $this->get('/')->assertSee('Near Galle')->assertSee('Galle Fort Turf');

        $this->delete(route('location.destroy'))->assertRedirect();
        $this->get('/')->assertDontSee('Near Galle');
    }

    public function test_venue_listing_sorts_by_distance_and_shows_it(): void
    {
        $this->post(route('location.store'), ['district' => 'Jaffna']);

        $page = $this->get('/venues')->assertOk()->assertSee('Nearest first');
        $content = $page->getContent();
        $this->assertLessThan(strpos($content, 'CR7 Futsal Arena'), strpos($content, 'Jaffna Sports Club Courts'), 'Jaffna venue listed before Colombo venues');
        $this->assertStringContainsString('&lt;1 km away', $content);

        $this->get('/venues?sort=name')->assertOk();
        $this->get('/venues?sort=rating')->assertOk();
    }

    public function test_activity_tiles_are_ordered_by_what_the_visitor_plays_and_what_is_close(): void
    {
        $customer = User::factory()->create(['role_id' => Role::Customer]);
        $bowling = Service::where('name', 'Ten-pin Lanes')->firstOrFail();
        Booking::create([
            'user_id' => $customer->id, 'venue_id' => $bowling->venue_id, 'service_id' => $bowling->id, 'service_option_id' => $bowling->defaultOption()->id,
            'starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(3)->addHour(), 'slots' => 1, 'unit_price' => 3200, 'subtotal' => 3200, 'total' => 3200,
            'status' => BookingStatus::Completed, 'payment_method' => PaymentMethod::PayAtVenue, 'payment_status' => PaymentStatus::Paid, 'priority' => 1,
            'customer_name' => 'C', 'customer_phone' => '0771234567',
        ]);

        $this->actingAs($customer);
        $content = $this->get('/')->assertOk()->assertSee('Book again')->assertSee('Ten-pin Lanes')->getContent();

        // Bowling tile carries the "for you" mark and comes before Futsal (admin order puts Futsal first).
        $this->assertStringContainsString('for you', $content);
        $this->assertLessThan(strpos($content, 'activity=futsal'), strpos($content, 'activity=bowling'));

        // Without any affinity but with a location, the closest activity's venue distance is shown on the tile.
        $guest = $this->withSession([]);
        $guest->post(route('location.store'), ['district' => 'Kandy']);
        $guest->get('/')->assertOk()->assertSee('nearest');
    }

    public function test_viewing_a_venue_is_remembered_for_recently_viewed(): void
    {
        $venue = Venue::where('slug', 'strike-zone-bowling')->firstOrFail();
        $this->get(route('venues.show', $venue))->assertOk();

        $this->assertSame(1, VenueView::where('venue_id', $venue->id)->count());
        $this->get('/')->assertOk()->assertSee('Recently viewed')->assertSee('Strike Zone Bowling');

        // Suspended/hidden venues drop out of the list.
        $venue->update(['is_approved' => false]);
        $this->get('/')->assertDontSee('Recently viewed');
    }

    public function test_logged_in_users_keep_their_location_across_sessions(): void
    {
        $user = User::factory()->create(['role_id' => Role::Customer]);
        $this->actingAs($user)->post(route('location.store'), ['district' => 'Galle']);
        $this->assertSame('Galle', $user->fresh()->location_label);
        $this->assertEqualsWithDelta(6.0535, $user->fresh()->latitude, 0.001);

        $fresh = $this->actingAs($user->fresh())->withSession([]);
        $this->assertSame('Galle', app(PersonalizationService::class)->location($fresh->get('/')->baseRequest)['label']);
    }
}
