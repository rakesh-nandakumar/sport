<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\ServiceOption;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Absolute opening bounds for a "session day". A venue open 10:00–02:00 on Friday has a
     * Friday session that ends at 02:00 on Saturday.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public function windowBounds(Service $service, CarbonInterface $day): ?array
    {
        $window = $service->windowFor($day->dayOfWeek);
        if (! $window) {
            return null;
        }

        [$opens, $closes] = $window;
        $start = Carbon::parse($day->toDateString().' '.$opens);
        $end = Carbon::parse($day->toDateString().' '.$closes);
        if ($end->lte($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    /**
     * Which session day a start time belongs to. 01:00 Saturday belongs to Friday's session when
     * Friday closes after midnight. Null when the venue is closed at that moment.
     */
    public function sessionDayFor(Service $service, CarbonInterface $start): ?Carbon
    {
        $start = Carbon::instance($start);
        foreach ([$start->copy()->startOfDay(), $start->copy()->startOfDay()->subDay()] as $day) {
            $bounds = $this->windowBounds($service, $day);
            if ($bounds && $start->gte($bounds[0]) && $start->lt($bounds[1])) {
                return $day;
            }
        }

        return null;
    }

    /**
     * Every start slot for a session day with how many units of the option are free, whether the
     * slot is bookable at all (free, not in the past/lead time AND enough contiguous free blocks to
     * satisfy the service minimum) and the longest booking that can start there.
     *
     * @return Collection<int, array{start: Carbon, end: Carbon, label: string, free: int, available: bool, bookable: bool, max_blocks: int}>
     */
    public function slotsForDay(Service $service, ServiceOption $option, CarbonInterface $day): Collection
    {
        $bounds = $this->windowBounds($service, $day);
        if (! $bounds) {
            return collect();
        }

        [$dayStart, $dayEnd] = $bounds;
        $bookings = $this->holdingBookings($service, $option, $dayStart, $dayEnd);
        $earliest = now()->addMinutes($service->lead_time_minutes);

        $slots = [];
        for ($cursor = $dayStart->copy(); $cursor->copy()->addMinutes($service->slot_minutes)->lte($dayEnd); $cursor->addMinutes($service->slot_minutes)) {
            $slotEnd = $cursor->copy()->addMinutes($service->slot_minutes);
            $free = max(0, $option->capacity - $this->unitsUsed($bookings, $cursor, $slotEnd, $service->buffer_minutes));

            $slots[] = [
                'start' => $cursor->copy(),
                'end' => $slotEnd,
                'label' => $cursor->format('h:i A'),
                'free' => $free,
                'available' => $free > 0 && $cursor->gte($earliest),
                'bookable' => false,
                'max_blocks' => 0,
            ];
        }

        // Walk backwards so each slot knows how many free blocks follow it.
        $run = 0;
        for ($i = count($slots) - 1; $i >= 0; $i--) {
            $run = $slots[$i]['available'] ? $run + 1 : 0;
            $max = $service->max_slots ? min($run, $service->max_slots) : $run;
            $slots[$i]['max_blocks'] = $max;
            $slots[$i]['bookable'] = $slots[$i]['available'] && $max >= max(1, $service->min_slots);
        }

        return collect($slots);
    }

    /**
     * Maximum contiguous blocks that can be booked from $start (bounded by closing time, capacity in
     * every block and the service's max_slots). Pass the session day for after-midnight starts.
     */
    public function maxSlotsFrom(Service $service, ServiceOption $option, CarbonInterface $start, ?CarbonInterface $day = null): int
    {
        $day ??= $this->sessionDayFor($service, $start);
        if (! $day) {
            return 0;
        }

        $slot = $this->slotsForDay($service, $option, $day)->first(fn ($s) => $s['start']->equalTo($start));

        return $slot['max_blocks'] ?? 0;
    }

    /**
     * Bookings that still hold a unit somewhere inside [$start, $end) including the buffer.
     * Lower-priority ones may be replaced by the booking engine.
     */
    public function competingBookings(Service $service, ServiceOption $option, CarbonInterface $start, CarbonInterface $end, bool $lock = false): Collection
    {
        return Booking::query()
            ->where('service_id', $service->id)
            ->where('service_option_id', $option->id)
            ->stillHolding()
            ->overlapping($start, $end, $service->buffer_minutes)
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->orderBy('priority')
            ->orderByDesc('created_at')
            ->get();
    }

    /** Number of $bookings that occupy a unit during [$start, $end), buffer included. */
    public function unitsUsed(Collection $bookings, CarbonInterface $start, CarbonInterface $end, int $buffer): int
    {
        return $bookings->filter(fn (Booking $b) => $this->occupies($b, $start, $end, $buffer))->count();
    }

    public function occupies(Booking $booking, CarbonInterface $start, CarbonInterface $end, int $buffer): bool
    {
        return $booking->starts_at->copy()->subMinutes($buffer)->lt($end)
            && $booking->ends_at->copy()->addMinutes($buffer)->gt($start);
    }

    protected function holdingBookings(Service $service, ServiceOption $option, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Booking::query()
            ->where('service_id', $service->id)
            ->where('service_option_id', $option->id)
            ->stillHolding()
            ->overlapping($from, $to, $service->buffer_minutes)
            ->get(['id', 'starts_at', 'ends_at', 'priority']);
    }
}
