<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $venueIds = $request->user()->venues()->pluck('id');
        $base = Booking::whereIn('venue_id', $venueIds);

        $stats = [
            'today' => (clone $base)->active()->whereDate('starts_at', today())->count(),
            'week' => (clone $base)->active()->whereBetween('starts_at', [now()->startOfWeek(Carbon::SUNDAY), now()->endOfWeek(Carbon::SATURDAY)])->count(),
            'revenue_month' => (clone $base)->where('payment_status', PaymentStatus::Paid)->whereMonth('starts_at', now()->month)->whereYear('starts_at', now()->year)->sum('total'),
            'pending_verification' => (clone $base)->active()->where('payment_status', PaymentStatus::PendingVerification)->count(),
            'unconfirmed' => (clone $base)->where('status', BookingStatus::Pending)->where('starts_at', '>', now())->count(),
        ];

        $upcoming = (clone $base)->active()
            ->where('starts_at', '>=', now())
            ->with(['venue', 'service', 'option', 'user'])
            ->orderBy('starts_at')
            ->take(10)
            ->get();

        $venues = $request->user()->venues()->withCount('services')->get();

        return view('vendor.dashboard', compact('stats', 'upcoming', 'venues'));
    }

    /** FullCalendar feed for all the vendor's bookings. */
    public function events(Request $request): JsonResponse
    {
        $venueIds = $request->user()->venues()->pluck('id');

        $events = Booking::whereIn('venue_id', $venueIds)
            ->active()
            ->when($request->filled('start'), fn ($q) => $q->where('ends_at', '>=', $request->input('start')))
            ->when($request->filled('end'), fn ($q) => $q->where('starts_at', '<=', $request->input('end')))
            ->with(['service', 'option'])
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'title' => "{$b->service->name} · {$b->customer_name}",
                'start' => $b->starts_at->toIso8601String(),
                'end' => $b->ends_at->toIso8601String(),
                'url' => route('vendor.bookings.show', $b),
                'color' => $b->status === BookingStatus::Confirmed ? '#198754' : '#f59e0b',
            ]);

        return response()->json($events);
    }
}
