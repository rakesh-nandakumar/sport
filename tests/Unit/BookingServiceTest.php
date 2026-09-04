<?php

namespace Tests\Unit;

use App\Models\Resource;
use App\Services\BookingService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class BookingServiceTest extends TestCase
{
    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BookingService();
    }

    public function test_calculate_total_applies_rate_per_hour(): void
    {
        $resource = new Resource(['rate' => 1200, 'pricing_unit' => 'per_hour']);

        $this->assertSame(
            1200.0,
            $this->service->calculateTotal($resource, Carbon::parse('2026-09-14 10:00'), Carbon::parse('2026-09-14 11:00'))
        );
    }

    public function test_calculate_total_prorates_partial_hours(): void
    {
        $resource = new Resource(['rate' => 1000, 'pricing_unit' => 'per_hour']);

        $this->assertSame(
            1500.0,
            $this->service->calculateTotal($resource, Carbon::parse('2026-09-14 10:00'), Carbon::parse('2026-09-14 11:30'))
        );
    }

    public function test_calculate_total_per_game_multiplies_by_quantity(): void
    {
        $resource = new Resource(['rate' => 150, 'pricing_unit' => 'per_game']);

        $this->assertSame(
            450.0,
            $this->service->calculateTotal($resource, Carbon::parse('2026-09-14 10:00'), Carbon::parse('2026-09-14 11:00'), 3)
        );
    }

    public function test_calculate_total_per_person_multiplies_by_quantity(): void
    {
        $resource = new Resource(['rate' => 250, 'pricing_unit' => 'per_person']);

        $this->assertSame(
            1000.0,
            $this->service->calculateTotal($resource, Carbon::parse('2026-09-14 10:00'), Carbon::parse('2026-09-14 11:00'), 4)
        );
    }

    public function test_calculate_total_per_session_is_flat(): void
    {
        $resource = new Resource(['rate' => 500, 'pricing_unit' => 'per_session']);

        $this->assertSame(
            500.0,
            $this->service->calculateTotal($resource, Carbon::parse('2026-09-14 10:00'), Carbon::parse('2026-09-14 12:00'), 3)
        );
    }

    public function test_is_within_opening_hours_respects_venue_window(): void
    {
        $indoor = new \App\Models\Indoor([
            'monday_opening' => '09:00:00',
            'monday_closing' => '17:00:00',
        ]);

        $resource = new Resource();
        $resource->setRelation('indoor', $indoor);

        $inside = $this->service->isWithinOpeningHours(
            $resource,
            Carbon::parse('2026-09-14 10:00'), // a Monday
            Carbon::parse('2026-09-14 11:00')
        );

        $outside = $this->service->isWithinOpeningHours(
            $resource,
            Carbon::parse('2026-09-14 18:00'),
            Carbon::parse('2026-09-14 19:00')
        );

        $this->assertTrue($inside);
        $this->assertFalse($outside);
    }

    public function test_is_within_opening_hours_handles_past_midnight_closing(): void
    {
        $resource = new Resource([
            'opening_hours' => ['monday' => ['open' => '20:00', 'close' => '01:00']],
        ]);

        $inside = $this->service->isWithinOpeningHours(
            $resource,
            Carbon::parse('2026-09-14 22:00'),
            Carbon::parse('2026-09-15 00:30')
        );

        $outside = $this->service->isWithinOpeningHours(
            $resource,
            Carbon::parse('2026-09-14 18:00'),
            Carbon::parse('2026-09-14 19:00')
        );

        $this->assertTrue($inside);
        $this->assertFalse($outside);
    }

    public function test_is_within_opening_hours_treats_null_hours_as_closed(): void
    {
        $resource = new Resource();

        $this->assertFalse(
            $this->service->isWithinOpeningHours(
                $resource,
                Carbon::parse('2026-09-14 10:00'),
                Carbon::parse('2026-09-14 11:00')
            )
        );
    }
}
