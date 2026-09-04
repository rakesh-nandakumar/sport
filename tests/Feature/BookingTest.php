<?php

namespace Tests\Feature;

use App\Exceptions\BookingUnavailableException;
use App\Models\Indoor;
use App\Models\Resource;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Database\Seeders\ActivitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ActivitySeeder::class);

        $this->service = app(BookingService::class);
    }

    private function customer(): User
    {
        return User::factory()->create(['role_id' => 5]);
    }

    private function owner(): User
    {
        return User::factory()->create(['role_id' => 2]);
    }

    private function venue(User $owner): Indoor
    {
        return Indoor::factory()->create(['user_id' => $owner->id]);
    }

    private function times(int $startHour = 10, int $endHour = 11, int $daysAhead = 1): array
    {
        $start = now()->addDays($daysAhead)->setTime($startHour, 0);

        return [
            'start_time' => $start->format('Y-m-d\TH:i'),
            'finish_time' => $start->copy()->setTime($endHour, 0)->format('Y-m-d\TH:i'),
        ];
    }

    public function test_concurrent_bookings_on_different_resources_of_one_venue_both_succeed(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $courtA = Resource::factory()->create(['indoor_id' => $venue->id]);
        $ps5 = Resource::factory()->ps5()->create(['indoor_id' => $venue->id]);
        $customer = $this->customer();
        $data = $this->times();

        $this->service->create($courtA, $customer, $data);
        $booking2 = $this->service->create($ps5, $customer, $data);

        $this->assertDatabaseHas('bookings', [
            'resource_id' => $courtA->id,
            'indoor_id' => $venue->id,
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking2->id,
            'resource_id' => $ps5->id,
        ]);
    }

    public function test_overlapping_booking_on_the_same_resource_fails(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $courtA = Resource::factory()->create(['indoor_id' => $venue->id]);
        $customer = $this->customer();
        $data = $this->times();

        $this->service->create($courtA, $customer, $data);

        $this->expectException(BookingUnavailableException::class);

        $this->service->create($courtA, $customer, $this->times(10, 11));
    }

    public function test_partially_overlapping_booking_fails(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $courtA = Resource::factory()->create(['indoor_id' => $venue->id]);
        $customer = $this->customer();

        $this->service->create($courtA, $customer, $this->times(10, 12));

        $this->expectException(BookingUnavailableException::class);

        $this->service->create($courtA, $customer, $this->times(11, 12));
    }

    public function test_cancelled_booking_does_not_block_the_slot(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $courtA = Resource::factory()->create(['indoor_id' => $venue->id]);
        $customer = $this->customer();
        $data = $this->times();

        $booking = $this->service->create($courtA, $customer, $data);

        $this->service->cancel($booking, $customer);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);

        $this->service->create($courtA, $customer, $data);
    }

    public function test_booking_outside_opening_hours_fails(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $venue->update([
            'monday_opening' => '09:00:00',
            'monday_closing' => '17:00:00',
        ]);
        $courtA = Resource::factory()->create(['indoor_id' => $venue->id]);
        $customer = $this->customer();

        $monday = now()->next(Carbon::MONDAY)->setTime(18, 0);

        $this->expectException(BookingUnavailableException::class);

        $this->service->create($courtA, $customer, [
            'start_time' => $monday->format('Y-m-d\TH:i'),
            'finish_time' => $monday->copy()->setTime(19, 0)->format('Y-m-d\TH:i'),
        ]);
    }

    public function test_booking_in_the_past_fails(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $courtA = Resource::factory()->create(['indoor_id' => $venue->id]);
        $customer = $this->customer();

        $this->expectException(BookingUnavailableException::class);

        $this->service->create(
            $courtA,
            $customer,
            [
                'start_time' => now()->subHours(3)->format('Y-m-d\TH:i'),
                'finish_time' => now()->subHours(2)->format('Y-m-d\TH:i'),
            ]
        );
    }

    public function test_total_price_is_stored_for_each_pricing_unit(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $customer = $this->customer();

        $perHour = Resource::factory()->create(['indoor_id' => $venue->id, 'rate' => 1200, 'pricing_unit' => 'per_hour']);
        $perSession = Resource::factory()->perSession()->create(['indoor_id' => $venue->id]);
        $perGame = Resource::factory()->pool()->create(['indoor_id' => $venue->id]);
        $perPerson = Resource::factory()->perPerson()->create(['indoor_id' => $venue->id]);

        $data = $this->times();

        $this->service->create($perHour, $customer, $data);
        $this->assertDatabaseHas('bookings', ['resource_id' => $perHour->id, 'total_price' => 1200.00]);

        $this->service->create($perSession, $customer, $data);
        $this->assertDatabaseHas('bookings', ['resource_id' => $perSession->id, 'total_price' => 500.00]);

        $this->service->create($perGame, $customer, array_merge($data, ['unit_quantity' => 3]));
        $this->assertDatabaseHas('bookings', ['resource_id' => $perGame->id, 'total_price' => 450.00, 'unit_quantity' => 3]);

        $this->service->create($perPerson, $customer, array_merge($data, ['unit_quantity' => 4]));
        $this->assertDatabaseHas('bookings', ['resource_id' => $perPerson->id, 'total_price' => 1000.00, 'unit_quantity' => 4]);
    }

    public function test_customer_can_book_through_http_endpoint(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $courtA = Resource::factory()->create(['indoor_id' => $venue->id]);
        $customer = $this->customer();

        $response = $this->actingAs($customer)->post("/home/{$venue->id}/book", array_merge(
            $this->times(),
            [
                'resource_id' => $courtA->id,
                'phoneNumber' => '0771234567',
                'custName' => $customer->name,
            ]
        ));

        $response->assertRedirect('/');
        $response->assertSessionHas('message');

        $this->assertDatabaseHas('bookings', [
            'resource_id' => $courtA->id,
            'indoor_id' => $venue->id,
            'user_id' => $customer->id,
            'status' => 'confirmed',
            'total_price' => 1200.00,
        ]);
    }

    public function test_guest_cannot_book(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $courtA = Resource::factory()->create(['indoor_id' => $venue->id]);

        $response = $this->post("/home/{$venue->id}/book", array_merge(
            $this->times(),
            [
                'resource_id' => $courtA->id,
                'phoneNumber' => '0771234567',
                'custName' => 'Guest',
            ]
        ));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_resource_must_belong_to_the_route_venue(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $otherVenue = $this->venue($owner);
        $otherResource = Resource::factory()->create(['indoor_id' => $otherVenue->id]);
        $customer = $this->customer();

        $response = $this->actingAs($customer)->post("/home/{$venue->id}/book", array_merge(
            $this->times(),
            [
                'resource_id' => $otherResource->id,
                'phoneNumber' => '0771234567',
                'custName' => $customer->name,
            ]
        ));

        $response->assertSessionHasErrors('resource_id');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_non_owner_cannot_cancel_a_booking(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $courtA = Resource::factory()->create(['indoor_id' => $venue->id]);
        $customer = $this->customer();
        $stranger = $this->customer();

        $booking = $this->service->create($courtA, $customer, $this->times());

        $response = $this->actingAs($stranger)->post("/cancel-booking/{$booking->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
    }

    public function test_owner_can_cancel_a_booking(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $courtA = Resource::factory()->create(['indoor_id' => $venue->id]);
        $customer = $this->customer();

        $booking = $this->service->create($courtA, $customer, $this->times());

        $response = $this->actingAs($owner)->post("/cancel-booking/{$booking->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
    }

    public function test_duration_under_minimum_fails(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $courtA = Resource::factory()->create(['indoor_id' => $venue->id, 'min_duration_minutes' => 60]);
        $customer = $this->customer();

        $this->expectException(BookingUnavailableException::class);

        $this->service->create($courtA, $customer, $this->times(10, 10));
    }

    public function test_availability_feed_is_scoped_to_one_resource(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $courtA = Resource::factory()->create(['indoor_id' => $venue->id]);
        $courtB = Resource::factory()->create(['indoor_id' => $venue->id, 'name' => 'Court B']);
        $customer = $this->customer();

        $this->service->create($courtA, $customer, $this->times());

        $response = $this->getJson("/resources/{$courtA->id}/availability");
        $response->assertOk()->assertJsonCount(1);

        $response = $this->getJson("/resources/{$courtB->id}/availability");
        $response->assertOk()->assertJsonCount(0);
    }

    public function test_overlapping_http_booking_returns_validation_error(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        $courtA = Resource::factory()->create(['indoor_id' => $venue->id]);
        $customer = $this->customer();

        $this->service->create($courtA, $customer, $this->times());

        $response = $this->actingAs($customer)->post("/home/{$venue->id}/book", array_merge(
            $this->times(),
            [
                'resource_id' => $courtA->id,
                'phoneNumber' => '0771234567',
                'custName' => $customer->name,
            ]
        ));

        $response->assertSessionHasErrors('message');
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_venue_page_and_owner_resources_page_render(): void
    {
        $owner = $this->owner();
        $venue = $this->venue($owner);
        Resource::factory()->ps5()->create([
            'indoor_id' => $venue->id,
            'custom_fields' => ['game_library' => ['EA FC 25', 'GTA V']],
        ]);
        $customer = $this->customer();

        $this->get("/home/{$venue->id}")->assertOk();
        $this->actingAs($customer)->get("/home/{$venue->id}")->assertOk();
        $this->actingAs($owner)->get("/home/{$venue->id}/resources")->assertOk();

        $response = $this->actingAs($owner)->get("/home/{$venue->id}");

        $response->assertOk();
    }
}
