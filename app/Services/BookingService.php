<?php

namespace App\Services;

use App\Exceptions\BookingUnavailableException;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\User;
use App\SystemMessageNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class BookingService
{
    /**
     * Venue hours for that weekday, unless the resource overrides them.
     * Handles past-midnight closing (e.g. close 01:00).
     */
    public function isWithinOpeningHours(Resource $resource, CarbonInterface $start, CarbonInterface $end): bool
    {
        $hours = $resource->hoursFor($start);

        if (! $hours) {
            return false;
        }

        $openMinutes = $this->minutesOfDay($hours['open']);
        $closeMinutes = $this->minutesOfDay($hours['close']);

        $startMinutes = $start->hour * 60 + $start->minute;
        $endMinutes = $end->hour * 60 + $end->minute;

        if ($end->greaterThan($start) && ! $end->isSameDay($start)) {
            $endMinutes += 1440;
        }

        if ($closeMinutes < $openMinutes) {
            $closeMinutes += 1440;
        }

        return $startMinutes >= $openMinutes && $endMinutes <= $closeMinutes;
    }

    /** Overlap on resource_id, ignoring cancelled bookings. */
    public function hasConflict(Resource $resource, CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): bool
    {
        return Booking::query()
            ->where('resource_id', $resource->id)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreBookingId !== null, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->where('start_time', '<', $end)
            ->where('finish_time', '>', $start)
            ->exists();
    }

    /** Rate x hours for per_hour; rate x unit_quantity for per_game/per_person; flat rate for per_session. */
    public function calculateTotal(Resource $resource, CarbonInterface $start, CarbonInterface $end, int $quantity = 1): float
    {
        $rate = (float) $resource->rate;

        return match ($resource->pricing_unit) {
            'per_hour' => round($rate * ($start->diffInMinutes($end) / 60), 2),
            'per_game', 'per_person' => round($rate * $quantity, 2),
            default => $rate,
        };
    }

    /**
     * finish > start, min_duration_minutes, not in the past, within hours, no conflict.
     *
     * @throws BookingUnavailableException
     */
    public function assertBookable(Resource $resource, CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): void
    {
        if ($end->lessThanOrEqualTo($start)) {
            throw new BookingUnavailableException('The end time must be after the start time.');
        }

        if ($start->diffInMinutes($end) < $resource->min_duration_minutes) {
            throw new BookingUnavailableException("This resource requires a minimum of {$resource->min_duration_minutes} minutes.");
        }

        if ($start->isPast()) {
            throw new BookingUnavailableException('The start time must be in the future.');
        }

        if (! $this->isWithinOpeningHours($resource, $start, $end)) {
            throw new BookingUnavailableException('The selected slot is outside the opening hours of this resource.');
        }

        if ($this->hasConflict($resource, $start, $end, $ignoreBookingId)) {
            throw new BookingUnavailableException('The selected time slot is already booked. Please choose a different time.');
        }
    }

    /**
     * Transaction + lockForUpdate on the resource row, re-checks conflict inside the
     * lock, persists, notifies both parties.
     *
     * @param  array{start_time: string, finish_time: string, phoneNumber?: string, custName?: string, unit_quantity?: int, selected_options?: array}  $data
     */
    public function create(Resource $resource, User $user, array $data): Booking
    {
        $start = \Carbon\Carbon::parse($data['start_time']);
        $end = \Carbon\Carbon::parse($data['finish_time']);

        return DB::transaction(function () use ($resource, $user, $data, $start, $end) {
            $locked = Resource::whereKey($resource->id)->lockForUpdate()->firstOrFail();

            $this->assertBookable($locked, $start, $end);

            $quantity = max(1, (int) ($data['unit_quantity'] ?? 1));

            $booking = Booking::create([
                'resource_id' => $locked->id,
                'indoor_id' => $locked->indoor_id,
                'user_id' => $user->id,
                'start_time' => $start,
                'finish_time' => $end,
                'status' => 'confirmed',
                'unit_quantity' => $quantity,
                'selected_options' => $data['selected_options'] ?? null,
                'comments' => 'Booked',
                'phoneNumber' => $data['phoneNumber'] ?? '',
                'custName' => $data['custName'] ?? $user->name,
                'total_price' => $this->calculateTotal($locked, $start, $end, $quantity),
            ]);

            $this->notify($booking);

            return $booking;
        });
    }

    /** Status -> cancelled (soft), authorizes actor (booking owner or venue owner), notifies. */
    public function cancel(Booking $booking, User $actor): void
    {
        $canCancel = $booking->user_id === $actor->id
            || $booking->indoor?->user_id === $actor->id;

        if (! $canCancel) {
            abort(403, 'Unauthorized action');
        }

        $booking->update(['status' => 'cancelled']);

        $indoor = $booking->indoor;

        if ($indoor) {
            $indoorOwner = $indoor->user;

            if ($indoorOwner) {
                $indoorOwner->notify(new SystemMessageNotification(
                    'Someone has cancelled the booking from ' . $booking->start_time . ' to ' . $booking->finish_time . ' on ' . now()->format('Y-m-d H:i:s') . ' at ' . $indoor->title,
                    'danger',
                    'Cancelled',
                    'danger'
                ));
            }
        }

        $actor->notify(new SystemMessageNotification(
            'Your booking has been cancelled successfully! from ' . $booking->start_time . ' to ' . $booking->finish_time . ' on ' . now()->format('Y-m-d H:i:s') . ' at ' . ($indoor?->title ?? ''),
            'danger',
            'Cancelled',
            'danger'
        ));
    }

    private function notify(Booking $booking): void
    {
        $indoor = $booking->indoor;
        $startDateTime = $booking->start_time->format('Y-m-d H:i');
        $finishDateTime = $booking->finish_time->format('Y-m-d H:i');
        $bookingDateTime = now()->format('Y-m-d H:i:s');

        if ($indoor) {
            $indoorOwner = $indoor->user;

            if ($indoorOwner) {
                $indoorOwner->notify(new SystemMessageNotification(
                    'Someone has booked your indoor from ' . $indoor->title . $startDateTime . ' to ' . $finishDateTime . ' on ' . $bookingDateTime,
                    'success',
                    'Success',
                    'success'
                ));
            }
        }

        $booking->user->notify(new SystemMessageNotification(
            'Your booking has been created successfully from ' . $startDateTime . ' to ' . $finishDateTime . ' at ' . ($indoor?->title ?? ''),
            'success',
            'Success',
            'success'
        ));
    }

    private function minutesOfDay(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hours) * 60 + (int) $minutes;
    }
}
