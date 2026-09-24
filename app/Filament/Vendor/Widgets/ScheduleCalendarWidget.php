<?php

namespace App\Filament\Vendor\Widgets;

use App\Enums\BookingStatus;
use App\Filament\Vendor\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Widgets\Widget;

class ScheduleCalendarWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.vendor.widgets.schedule-calendar';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 3;

    /**
     * FullCalendar's event source calls this directly via $wire — no separate HTTP route needed.
     *
     * @return array<int, array<string, mixed>>
     */
    public function events(string $start, string $end): array
    {
        $venueIds = auth()->user()->venues()->pluck('id');

        return Booking::whereIn('venue_id', $venueIds)
            ->active()
            ->where('ends_at', '>=', $start)
            ->where('starts_at', '<=', $end)
            ->with(['service'])
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'title' => "{$booking->service->name} · {$booking->customer_name}",
                'start' => $booking->starts_at->toIso8601String(),
                'end' => $booking->ends_at->toIso8601String(),
                'url' => BookingResource::getUrl('view', ['record' => $booking]),
                'color' => $booking->status === BookingStatus::Confirmed ? '#198754' : '#f59e0b',
            ])
            ->all();
    }
}
