<?php

namespace App\Filament\Vendor\Widgets;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VendorStatsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $venueIds = auth()->user()->venues()->pluck('id');
        $base = Booking::whereIn('venue_id', $venueIds);

        $revenueMonth = (clone $base)->where('payment_status', PaymentStatus::Paid)
            ->whereMonth('starts_at', now()->month)
            ->whereYear('starts_at', now()->year)
            ->sum('total');

        return [
            Stat::make('Today', (clone $base)->active()->whereDate('starts_at', today())->count())
                ->icon('heroicon-o-calendar-days')
                ->color('primary'),
            Stat::make('This week', (clone $base)->active()->whereBetween('starts_at', [now()->startOfWeek(Carbon::SUNDAY), now()->endOfWeek(Carbon::SATURDAY)])->count())
                ->icon('heroicon-o-calendar')
                ->color('info'),
            Stat::make('Revenue this month', lkr((float) $revenueMonth))
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Transfers to verify', (clone $base)->active()->where('payment_status', PaymentStatus::PendingVerification)->count())
                ->icon('heroicon-o-document-currency-dollar')
                ->color('warning'),
            Stat::make('Unconfirmed holds', (clone $base)->where('status', BookingStatus::Pending)->where('starts_at', '>', now())->count())
                ->icon('heroicon-o-clock')
                ->color('gray'),
        ];
    }
}
