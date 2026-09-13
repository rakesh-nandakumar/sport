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
     * Every possible start slot for a service on a given day, with how many units of the
     * chosen option are still free in that slot. Slots in the past or inside the lead time
     * are reported as unavailable so the UI can grey them out.
     *
     * @return Collection<int, array{start: Carbon, end: Carbon, label: string, free: int, available: bool}>
     */
    public function slotsForDay(Service $service, ServiceOption $option, CarbonInterface $day): Collection
    {
        $window = $service->windowFor($day->dayOfWeek);
        if (! $window) {
            return collect();
        }

        [$opens, $closes] = $window;
        $dayStart = Carbon::parse($day->toDateString().' '.$opens);
        $dayEnd = Carbon::parse($day->toDateString().' '.$closes);
        if ($dayEnd->lte($dayStart)) {
            $dayEnd->addDay(); // closes after midnight
        }

        $bookings = $this->activeBookings($service, $option, $dayStart, $dayEnd);
        $earliest = now()->addMinutes($service->lead_time_minutes);

        $slots = collect();
        for ($cursor = $dayStart->copy(); $cursor->copy()->addMinutes($service->slot_minutes)->lte($dayEnd); $cursor->addMinutes($service->slot_minutes)) {
            $slotEnd = $cursor->copy()->addMinutes($service->slot_minutes);
            $used = $this->unitsUsed($bookings, $cursor, $slotEnd, $service->buffer_minutes);
            $free = max(0, $option->capacity - $used);

            $slots->push([
                'start' => $cursor->copy(),
                'end' => $slotEnd,
                'label' => $cursor->format('h:i A'),
                'free' => $free,
                'available' => $free > 0 && $cursor->gte($earliest),
            ]);
        }

        return $slots;
    }

    /**
     * Maximum contiguous slots that can be booked starting at $start (bounded by closing time,
     * option capacity and the service's max_slots).
     */
    public function maxSlotsFrom(Service $service, ServiceOption $option, CarbonInterface $start): int
    {
        $slots = $this->slotsForDay($service, $option, $start);
        $index = $slots->search(fn ($s) => $s['start']->equalTo($start));
        if ($index === false) {
            return 0;
        }

        $count = 0;
        foreach ($slots->slice($index) as $slot) {
            if (! $slot['available']) {
                break;
            }
            $count++;
            if ($service->max_slots && $count >= $service->max_slots) {
                break;
            }
        }

        return $count;
    }

    /**
     * Bookings that overlap [$start, $end) and still hold the slot.
     * Lower-priority ones may be replaced by the booking engine.
     */
    public function competingBookings(Service $service, ServiceOption $option, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return Booking::query()
            ->where('service_id', $service->id)
            ->where('service_option_id', $option->id)
            ->active()
            ->overlapping($start, $end, $service->buffer_minutes)
            ->orderBy('priority')
            ->orderByDesc('created_at')
            ->get();
    }

    protected function activeBookings(Service $service, ServiceOption $option, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Booking::query()
            ->where('service_id', $service->id)
            ->where('service_option_id', $option->id)
            ->active()
            ->overlapping($from, $to, $service->buffer_minutes)
            ->get(['id', 'starts_at', 'ends_at', 'priority']);
    }

    protected function unitsUsed(Collection $bookings, CarbonInterface $start, CarbonInterface $end, int $buffer): int
    {
        return $bookings->filter(function (Booking $b) use ($start, $end, $buffer) {
            return $b->starts_at->copy()->subMinutes($buffer)->lt($end)
                && $b->ends_at->copy()->addMinutes($buffer)->gt($start);
        })->count();
    }
}
