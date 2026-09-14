<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $revenueThisMonth = Booking::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereMonth('starts_at', now()->month)
            ->whereYear('starts_at', now()->year)
            ->sum('total');

        return [
            Stat::make('Venues', Venue::count())
                ->description(Venue::where('is_approved', false)->count().' hidden from customers')
                ->descriptionIcon('heroicon-m-eye-slash')
                ->icon('heroicon-o-building-storefront')
                ->color('primary'),
            Stat::make('Vendors', VendorProfile::count())
                ->description(VendorProfile::where('status', 'pending')->count().' waiting for review')
                ->descriptionIcon('heroicon-m-clock')
                ->icon('heroicon-o-briefcase')
                ->color('warning'),
            Stat::make('Customers', User::where('role_id', Role::Customer)->count())
                ->icon('heroicon-o-users')
                ->color('info'),
            Stat::make('Bookings this month', Booking::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count())
                ->description(Booking::whereDate('created_at', today())->count().' today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->icon('heroicon-o-calendar-days')
                ->color('success'),
            Stat::make('Paid revenue this month', lkr((float) $revenueThisMonth))
                ->description('Bookings paid for, by start date')
                ->descriptionIcon('heroicon-m-banknotes')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
        ];
    }
}
