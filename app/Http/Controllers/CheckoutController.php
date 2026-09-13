<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Exceptions\SlotUnavailableException;
use App\Models\Game;
use App\Models\Service;
use App\Models\ServiceOption;
use App\Services\BookingService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public const SESSION_KEY = 'checkout.draft';

    public function __construct(protected PricingService $pricing, protected BookingService $bookings) {}

    public function show(Request $request): View|RedirectResponse
    {
        $draft = $request->session()->get(self::SESSION_KEY);
        if (! $draft) {
            return redirect()->route('venues.index')->with('message', 'Pick a venue and build your booking first.');
        }

        $service = Service::with(['venue', 'activityType', 'rates', 'options'])->findOrFail($draft['service_id']);
        $option = $service->options->firstWhere('id', $draft['option_id']);
        $game = $draft['game_id'] ? Game::find($draft['game_id']) : null;
        $start = Carbon::parse($draft['starts_at']);
        $quote = $this->pricing->quote($service, $option, $start, $draft['slots']);

        return view('checkout.show', [
            'service' => $service,
            'venue' => $service->venue,
            'option' => $option,
            'game' => $game,
            'start' => $start,
            'end' => $start->copy()->addMinutes($draft['slots'] * $service->slot_minutes),
            'slots' => $draft['slots'],
            'players' => $draft['players'] ?? null,
            'quote' => $quote,
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $draft = $request->session()->get(self::SESSION_KEY);
        if (! $draft) {
            return redirect()->route('venues.index')->with('message', 'Your checkout session expired. Please rebuild your booking.');
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'min:3', 'max:100'],
            'customer_phone' => ['required', 'regex:/^0\d{9}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
            'players' => ['nullable', 'integer', 'min:1', 'max:100'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
        ], [
            'customer_phone.regex' => 'Enter a valid 10-digit number, e.g. 0771234567.',
        ]);

        $method = PaymentMethod::from($data['payment_method']);
        if (! $method->isAvailable()) {
            return back()->withErrors(['payment_method' => $method->label().' is coming soon. Please choose Pay at Venue or Bank Transfer.'])->withInput();
        }

        $service = Service::with(['venue', 'activityType', 'rates', 'options'])->findOrFail($draft['service_id']);
        $option = ServiceOption::findOrFail($draft['option_id']);
        $game = $draft['game_id'] ? Game::find($draft['game_id']) : null;

        try {
            $booking = $this->bookings->reserve(
                customer: $request->user(),
                service: $service,
                option: $option,
                start: Carbon::parse($draft['starts_at']),
                slots: (int) $draft['slots'],
                method: $method,
                details: $data,
                game: $game,
            );
        } catch (SlotUnavailableException $e) {
            return redirect()->route('booking.build', $service)->withErrors(['slot' => $e->getMessage()]);
        }

        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('bookings.show', $booking)->with('message', 'Booking '.$booking->reference.' placed!');
    }
}
