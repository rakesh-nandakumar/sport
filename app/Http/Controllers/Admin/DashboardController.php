<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use App\Models\Booking;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'venues' => Venue::count(),
            'pending_venues' => Venue::where('is_approved', false)->count(),
            'vendors' => User::where('role_id', Role::Vendor)->count(),
            'pending_vendors' => VendorProfile::where('status', VendorStatus::Pending->value)->count(),
            'customers' => User::where('role_id', Role::Customer)->count(),
            'bookings' => Booking::count(),
            'bookings_today' => Booking::whereDate('created_at', today())->count(),
            'bookings_month' => Booking::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'revenue_month' => Booking::where('payment_status', PaymentStatus::Paid)->whereMonth('starts_at', now()->month)->whereYear('starts_at', now()->year)->sum('total'),
        ];

        $days = collect(range(29, 0))->map(fn ($i) => today()->subDays($i));
        $bookingsPerDay = Booking::where('created_at', '>=', today()->subDays(29))
            ->get(['created_at'])
            ->groupBy(fn ($b) => $b->created_at->toDateString())
            ->map->count();

        $chart = [
            'labels' => $days->map->format('d M')->all(),
            'bookings' => $days->map(fn ($d) => $bookingsPerDay[$d->toDateString()] ?? 0)->all(),
        ];

        $byActivity = ActivityType::withCount('services')
            ->get()
            ->map(fn ($t) => ['name' => $t->name, 'count' => Booking::whereIn('service_id', $t->services()->select('id'))->count(), 'color' => $t->color])
            ->sortByDesc('count')
            ->values();

        $recent = Booking::with(['venue', 'service', 'user'])->latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'chart', 'byActivity', 'recent'));
    }
}
