<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(protected BookingService $bookings) {}

    public function index(Request $request): View
    {
        $venueIds = $request->user()->venues()->pluck('id');

        $bookings = Booking::whereIn('venue_id', $venueIds)
            ->with(['venue', 'service', 'option', 'user'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('venue'), fn ($q) => $q->where('venue_id', $request->input('venue')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('starts_at', $request->input('date')))
            ->when($request->filled('pending_payment'), fn ($q) => $q->where('payment_status', PaymentStatus::PendingVerification))
            ->orderByDesc('starts_at')
            ->paginate(20)
            ->withQueryString();

        return view('vendor.bookings.index', [
            'bookings' => $bookings,
            'venues' => $request->user()->venues,
            'statuses' => BookingStatus::cases(),
            'filters' => $request->only(['status', 'venue', 'date', 'pending_payment']),
        ]);
    }

    public function show(Request $request, Booking $booking): View
    {
        $this->authorizeOwner($request, $booking);
        $booking->load(['venue', 'service', 'option', 'game', 'user', 'payments.verifier', 'bumpedBy']);

        return view('vendor.bookings.show', ['booking' => $booking]);
    }

    public function confirm(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        abort_unless($booking->isActive(), 422);
        $this->bookings->vendorConfirm($booking);

        return back()->with('message', 'Booking confirmed and locked.');
    }

    public function markPaid(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        abort_unless($booking->isActive(), 422);
        $this->bookings->markPaid($booking, $request->user(), $request->input('reference'));

        return back()->with('message', 'Payment recorded. Booking is confirmed.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        abort_unless($booking->isActive(), 422);
        $this->bookings->cancel($booking, $request->user(), $request->input('reason'));

        return back()->with('message', 'Booking cancelled and the customer notified.');
    }

    public function complete(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        $this->bookings->complete($booking, $request->boolean('no_show'));

        return back()->with('message', $request->boolean('no_show') ? 'Marked as no-show.' : 'Marked as completed.');
    }

    protected function authorizeOwner(Request $request, Booking $booking): void
    {
        abort_unless($booking->venue->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
    }
}
