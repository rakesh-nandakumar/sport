<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class BookingsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Bookings — last 30 days';

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn (int $i) => today()->subDays($i));

        $perDay = Booking::query()
            ->where('created_at', '>=', today()->subDays(29))
            ->get(['created_at'])
            ->groupBy(fn (Booking $booking) => $booking->created_at->toDateString())
            ->map->count();

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data' => $days->map(fn ($day) => $perDay[$day->toDateString()] ?? 0)->all(),
                    'fill' => true,
                    'borderColor' => '#dc2626',
                    'backgroundColor' => 'rgba(220, 38, 38, 0.1)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $days->map->format('d M')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
