<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Exceptions\SlotUnavailableException;
use App\Models\Service;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class BookingBuilder extends Component
{
    use WithFileUploads;

    public Service $service;

    public int $optionId;

    public ?int $gameId = null;

    /** Session day (Y-m-d). For venues closing after midnight the 01:00 slot still belongs to this day. */
    public string $date;

    /** Chosen start as a full datetime (Y-m-d H:i:s) so after-midnight slots keep the right date. */
    public ?string $startAt = null;

    public int $blocks = 1;

    public ?int $players = null;

    public string $error = '';

    // Checkout modal
    public bool $showCheckout = false;

    /** A second, explicit acknowledgement for the provisional Pay at Venue path. */
    public bool $showPayAtVenueWarning = false;

    public string $customerName = '';

    public string $customerPhone = '';

    public string $notes = '';

    public string $paymentMethod = '';

    public string $bankReference = '';

    public $proof = null;

    public $nicFront = null;

    public $nicBack = null;

    public function mount(Service $service): void
    {
        $this->service = $service->load(['venue.hours', 'activityType', 'options', 'games', 'rates']);
        $this->optionId = $service->defaultOption()->id;
        $this->blocks = max(1, $service->min_slots);
        $this->date = $this->firstOpenDate()->toDateString();

        if ($user = auth()->user()) {
            $this->customerName = $user->name;
            $this->customerPhone = $user->phone ?? '';
        }
    }

    public function updatedOptionId(): void
    {
        $this->startAt = null;
        $this->error = '';
    }

    public function updatedDate(): void
    {
        $this->startAt = null;
        $this->error = '';
    }

    public function selectDate(string $date): void
    {
        $this->date = $date;
        $this->updatedDate();
    }

    public function selectTime(string $startAt): void
    {
        $this->startAt = $startAt;
        $this->error = '';
        $max = $this->maxSlots();
        if ($max > 0 && $this->blocks > $max) {
            $this->blocks = $max;
        }
    }

    public function incrementSlots(): void
    {
        if ($this->blocks < $this->maxSlots()) {
            $this->blocks++;
        }
    }

    public function decrementSlots(): void
    {
        if ($this->blocks > $this->service->min_slots) {
            $this->blocks--;
        }
    }

    /** "Checkout" — validates the plan and opens the payment modal (guests are sent to log in first). */
    public function checkout()
    {
        if (! auth()->check()) {
            session()->put('url.intended', route('booking.build', $this->service));

            return redirect()->route('login')->with('message', 'Log in or create a free account to finish your booking.');
        }

        if (! $this->planIsValid()) {
            return;
        }

        $available = array_values(array_filter(PaymentMethod::available(), fn (PaymentMethod $method) => $this->canUsePaymentMethod($method)));
        $this->paymentMethod = $this->paymentMethod && ($method = PaymentMethod::tryFrom($this->paymentMethod)) && $this->canUsePaymentMethod($method)
            ? $this->paymentMethod
            : ($available[0]->value ?? '');

        if (! $this->paymentMethod) {
            $this->error = 'No payment method is currently available for this account. Please contact support.';

            return;
        }
        $this->resetErrorBag();
        $this->showCheckout = true;
    }

    public function closeCheckout(): void
    {
        $this->showCheckout = false;
        $this->showPayAtVenueWarning = false;
    }

    public function selectPaymentMethod(string $method): void
    {
        $m = PaymentMethod::tryFrom($method);
        if ($m && $this->canUsePaymentMethod($m)) {
            $this->paymentMethod = $m->value;
            $this->showPayAtVenueWarning = false;
        }
    }

    /** Validate the ordinary checkout fields before showing the final Pay at Venue acknowledgement. */
    public function beginPayAtVenueConfirmation(): void
    {
        $data = $this->validatedCheckoutData();

        if (($data['paymentMethod'] ?? null) !== PaymentMethod::PayAtVenue->value) {
            $this->placeBooking();

            return;
        }

        if (auth()->user()?->isPayAtVenueBanned()) {
            $this->addError('paymentMethod', 'Pay at Venue is unavailable for your account. Please contact support if you believe this is a mistake.');

            return;
        }

        $this->showPayAtVenueWarning = true;
    }

    public function closePayAtVenueWarning(): void
    {
        $this->showPayAtVenueWarning = false;
    }

    /** The final Pay at Venue action: both NIC images are compulsory before a reservation is created. */
    public function confirmPayAtVenueBooking()
    {
        if (! $this->showPayAtVenueWarning || $this->paymentMethod !== PaymentMethod::PayAtVenue->value) {
            return;
        }

        return $this->placeBooking();
    }

    /** Places the booking from the modal and sends the customer to the confirmation page. */
    public function placeBooking()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }
        if (! auth()->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }
        if (! $this->planIsValid()) {
            $this->showCheckout = false;

            return;
        }

        $data = $this->validatedCheckoutData();

        $method = PaymentMethod::from($data['paymentMethod']);
        if (! $method->isAvailable()) {
            $this->addError('paymentMethod', $method->label().' is not available right now.');

            return;
        }

        if ($method === PaymentMethod::PayAtVenue && ! $this->showPayAtVenueWarning) {
            $this->addError('paymentMethod', 'Review the Pay at Venue conditions before placing this booking.');

            return;
        }

        if ($method === PaymentMethod::PayAtVenue && auth()->user()->isPayAtVenueBanned()) {
            $this->addError('paymentMethod', 'Pay at Venue is unavailable for your account. Please contact support if you believe this is a mistake.');

            return;
        }

        if ($method === PaymentMethod::PayAtVenue && ! auth()->user()->hasNicOnFile()) {
            $identity = $this->validate([
                'nicFront' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
                'nicBack' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            ], [
                'nicFront.required' => 'Upload or take a photo of the front of your NIC.',
                'nicBack.required' => 'Upload or take a photo of the back of your NIC.',
            ]);
            auth()->user()->update([
                'nic_front_path' => $identity['nicFront']->store('identity-documents/'.auth()->id(), 'local'),
                'nic_back_path' => $identity['nicBack']->store('identity-documents/'.auth()->id(), 'local'),
            ]);
            auth()->user()->refresh();
        }

        $game = $this->gameId ? $this->service->games->firstWhere('id', $this->gameId) : null;

        try {
            $booking = app(BookingService::class)->reserve(
                customer: auth()->user(),
                service: $this->service,
                option: $this->option(),
                start: $this->startsAt(),
                slots: $this->blocks,
                method: $method,
                details: [
                    'customer_name' => $data['customerName'],
                    'customer_phone' => $data['customerPhone'],
                    'notes' => $data['notes'] ?: null,
                    'players' => $this->players,
                    'bank_reference' => $data['bankReference'] ?: null,
                    'nic_front_path' => $method === PaymentMethod::PayAtVenue ? auth()->user()->nic_front_path : null,
                    'nic_back_path' => $method === PaymentMethod::PayAtVenue ? auth()->user()->nic_back_path : null,
                ],
                game: $game,
            );
        } catch (SlotUnavailableException $e) {
            $this->showCheckout = false;
            $this->showPayAtVenueWarning = false;
            $this->startAt = null;
            $this->error = $e->getMessage();

            return;
        }

        if ($method === PaymentMethod::BankTransfer && $this->proof) {
            $path = $this->proof->store('payment-proofs/'.$booking->id, 'local'); // private disk
            app(BookingService::class)->attachProof($booking, $path, $data['bankReference'] ?: null);
        }

        return redirect()->route('bookings.show', $booking)->with('message', 'Booking '.$booking->reference.' placed!');
    }

    /** @return array<string, mixed> */
    protected function validatedCheckoutData(): array
    {
        return $this->validate([
            'customerName' => ['required', 'string', 'min:3', 'max:100'],
            'customerPhone' => ['required', 'regex:/^0\d{9}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
            'players' => ['nullable', 'integer', 'min:1', 'max:'.($this->service->max_players ?: 100)],
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'bankReference' => ['nullable', 'string', 'max:100'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'customerPhone.regex' => 'Enter a valid 10-digit number, e.g. 0771234567.',
        ]);
    }

    public function canUsePaymentMethod(PaymentMethod $method): bool
    {
        return $method->isAvailable()
            && ! ($method === PaymentMethod::PayAtVenue && auth()->user()?->isPayAtVenueBanned());
    }

    public function hasNicOnFile(): bool
    {
        return auth()->user()?->hasNicOnFile() ?? false;
    }

    public function render()
    {
        $availability = app(AvailabilityService::class);
        $option = $this->option();
        $day = Carbon::parse($this->date);
        $slotsForDay = $availability->slotsForDay($this->service, $option, $day);

        // A previously chosen start may have been taken meanwhile — drop it rather than show a stale price.
        if ($this->startAt && ! $slotsForDay->first(fn ($s) => $s['bookable'] && $s['start']->toDateTimeString() === $this->startAt)) {
            $this->startAt = null;
        }

        $maxSlots = $this->maxSlots();
        $quote = $this->startAt
            ? app(PricingService::class)->quote($this->service, $option, $this->startsAt(), $this->blocks)
            : null;

        return view('livewire.booking-builder', [
            'option' => $option,
            'days' => $this->upcomingDays(),
            'timeSlots' => $slotsForDay,
            'maxSlots' => $maxSlots,
            'quote' => $quote,
            'startsAt' => $this->startAt ? $this->startsAt() : null,
            'endsAt' => $this->startAt ? $this->startsAt()->addMinutes($this->blocks * $this->service->slot_minutes) : null,
            'window' => $this->service->windowFor($day->dayOfWeek),
            'methods' => PaymentMethod::cases(),
            'holdMinutes' => (int) setting('payments.bank_transfer_hold_minutes'),
        ]);
    }

    protected function planIsValid(): bool
    {
        if (! $this->startAt) {
            $this->error = 'Pick a start time to continue.';

            return false;
        }

        if ($this->service->requiresGame() && ! $this->gameId) {
            $this->error = 'Choose the game you want to play.';

            return false;
        }

        $max = $this->maxSlots();
        if ($this->blocks < $this->service->min_slots || $this->blocks > $max) {
            $this->error = $max === 0
                ? 'That start time is no longer available.'
                : "You can book between {$this->service->min_slots} and {$max} blocks from this start time.";

            return false;
        }

        $this->error = '';

        return true;
    }

    protected function option()
    {
        return $this->service->options->firstWhere('id', $this->optionId) ?? $this->service->defaultOption();
    }

    protected function startsAt(): Carbon
    {
        return Carbon::parse($this->startAt);
    }

    protected function maxSlots(): int
    {
        if (! $this->startAt) {
            return $this->service->max_slots ?? 12;
        }

        return app(AvailabilityService::class)->maxSlotsFrom($this->service, $this->option(), $this->startsAt(), Carbon::parse($this->date));
    }

    /** Booking window as chips; days the venue is closed are flagged. */
    protected function upcomingDays(): array
    {
        $days = (int) setting('bookings.max_days_ahead');

        return collect(range(0, $days))->map(function ($i) {
            $d = today()->addDays($i);

            return [
                'date' => $d->toDateString(),
                'dow' => $d->format('D'),
                'day' => $d->format('d'),
                'month' => $d->format('M'),
                'open' => (bool) $this->service->windowFor($d->dayOfWeek),
                'today' => $i === 0,
            ];
        })->all();
    }

    protected function firstOpenDate(): Carbon
    {
        for ($i = 0; $i <= (int) setting('bookings.max_days_ahead'); $i++) {
            $d = today()->addDays($i);
            if ($this->service->windowFor($d->dayOfWeek)) {
                return $d;
            }
        }

        return today();
    }
}
