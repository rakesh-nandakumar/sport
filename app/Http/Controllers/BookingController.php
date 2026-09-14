<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    public function __construct(protected BookingService $bookings) {}

    /** The "customize your plan" page — renders the Livewire booking builder. */
    public function build(Service $service): View
    {
        abort_unless($service->is_active && $service->venue->isLive(), 404);
        $service->load(['venue.hours', 'activityType', 'options', 'games', 'rates']);

        return view('booking.build', ['service' => $service, 'venue' => $service->venue]);
    }

    public function index(Request $request): View
    {
        $this->bookings->expireStaleHolds();

        $bookings = $request->user()->bookings()
            ->with(['venue', 'service', 'option', 'game'])
            ->orderByDesc('starts_at')
            ->paginate(10);

        return view('bookings.index', ['bookings' => $bookings]);
    }

    public function show(Request $request, Booking $booking): View
    {
        $this->authorizeView($request, $booking);
        $booking->load(['venue', 'service', 'option', 'game', 'payments']);

        return view('bookings.show', ['booking' => $booking]);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeView($request, $booking);
        abort_unless($booking->isCancellable(), 422, 'This booking can no longer be cancelled.');

        $this->bookings->cancel($booking, $request->user(), $request->input('reason'));

        return back()->with('message', 'Booking '.$booking->reference.' cancelled.');
    }

    public function uploadProof(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeView($request, $booking);
        abort_unless($booking->payment_method === PaymentMethod::BankTransfer && $booking->isActive(), 422);

        $data = $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $path = $request->file('proof')->store('payment-proofs/'.$booking->id, 'local'); // private disk
        $this->bookings->attachProof($booking, $path, $data['reference'] ?? null);

        return back()->with('message', 'Slip uploaded. The venue will verify your transfer shortly.');
    }

    /** Bank slips are private: only the customer, the venue owner and admins can open them. */
    public function proof(Request $request, Booking $booking, Payment $payment): StreamedResponse
    {
        $this->authorizeView($request, $booking);
        abort_unless($payment->booking_id === $booking->id && $payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path);
    }

    protected function authorizeView(Request $request, Booking $booking): void
    {
        $user = $request->user();
        abort_unless($user->id === $booking->user_id || $user->isAdmin() || $user->id === $booking->venue->user_id, 403);
    }
}
