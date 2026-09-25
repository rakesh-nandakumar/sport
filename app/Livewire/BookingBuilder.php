<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Exceptions\SlotUnavailableException;
use App\Models\Game;
use App\Models\Service;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class BookingBuilder extends Component
{
    use WithFileUploads;

    public Service $service;

    public int $optionId;

    public ?int $gameId = null;

    /** @var array<int, int> Selected games. gameId remains as the first selection for legacy links/tests. */
    public array $gameIds = [];

    /** Session day (Y-m-d). For venues closing after midnight the 01:00 slot still belongs to this day. */
    public string $date;

    /** Chosen start as a full datetime (Y-m-d H:i:s) so after-midnight slots keep the right date. */
    public ?string $startAt = null;

    public int $blocks = 1;

    public ?int $players = null;

    public string $error = '';

    // Checkout modal
    public bool $showCheckout = false;

    /** booking = current activity only; order = every saved activity at this venue. */
    public string $checkoutMode = 'booking';

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

    public function toggleGame(int $gameId): void
    {
        if (! $this->service->games->contains('id', $gameId)) {
            return;
        }

        if (in_array($gameId, $this->gameIds, true)) {
            $this->gameIds = array_values(array_filter($this->gameIds, fn (int $id) => $id !== $gameId));
        } else {
            $this->gameIds[] = $gameId;
        }

        $this->gameIds = array_values(array_unique(array_map('intval', $this->gameIds)));
        $this->gameId = $this->gameIds[0] ?? null;
        $this->error = '';
    }

    public function updatedGameId(): void
    {
        if ($this->gameId && $this->service->games->contains('id', $this->gameId)) {
            $this->gameIds = [(int) $this->gameId];
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

        $this->checkoutMode = 'booking';
        $this->openCheckout();
    }

    /** Save this configured activity, then let the customer add another activity at this venue. */
    public function addToVenueOrder(): void
    {
        if (! $this->planIsValid()) {
            return;
        }

        $entry = [
            'key' => $this->cartItemKey(),
            'service_id' => $this->service->id,
            'option_id' => $this->option()->id,
            'start_at' => $this->startAt,
            'slots' => $this->blocks,
            'players' => $this->players,
            'game_ids' => $this->selectedGames()->pluck('id')->all(),
        ];
        $entries = collect($this->cartEntries())
            ->reject(fn (array $item) => $item['key'] === $entry['key'])
            ->push($entry)
            ->values()
            ->all();
        session()->put($this->cartSessionKey(), $entries);

        $this->error = '';
    }

    public function removeFromVenueOrder(string $key): void
    {
        session()->put($this->cartSessionKey(), collect($this->cartEntries())
            ->reject(fn (array $item) => $item['key'] === $key)
            ->values()
            ->all());
    }

    public function checkoutVenueOrder()
    {
        if (! auth()->check()) {
            session()->put('url.intended', route('booking.build', $this->service));

            return redirect()->route('login')->with('message', 'Log in or create a free account to finish your venue order.');
        }

        if ($this->cartBookingItems() === []) {
            $this->error = 'Add an activity to your venue order before checking out.';

            return;
        }

        $this->checkoutMode = 'order';
        $this->openCheckout();
    }

    protected function openCheckout(): void
    {

        $available = array_values(array_filter(PaymentMethod::available(), fn (PaymentMethod $method) => $this->canUsePaymentMethod($method)));
        $this->paymentMethod = $this->paymentMethod && ($method = PaymentMethod::tryFrom($this->paymentMethod)) && $this->canUsePaymentMethod($method)
            ? $this->paymentMethod
            : ($available[0]->value ?? '');

        if (! $this->paymentMethod) {
            $this->error = 'No payment method is currently available for this account at this venue. Please contact support.';

            return;
        }
        $this->resetErrorBag();
        $this->showCheckout = true;
    }

    public function closeCheckout(): void
    {
        $this->showCheckout = false;
        $this->showPayAtVenueWarning = false;
        $this->checkoutMode = 'booking';
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
        if ($this->checkoutMode === 'booking' && ! $this->planIsValid()) {
            $this->showCheckout = false;

            return;
        }
        if ($this->checkoutMode === 'order' && $this->cartBookingItems() === []) {
            $this->showCheckout = false;
            $this->error = 'Your venue order is empty. Add an activity before checking out.';

            return;
        }

        $data = $this->validatedCheckoutData();

        $method = PaymentMethod::from($data['paymentMethod']);
        if (! $this->service->venue->fresh()->allowsPaymentMethod($method)) {
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

        try {
            $details = [
                'customer_name' => $data['customerName'],
                'customer_phone' => $data['customerPhone'],
                'notes' => $data['notes'] ?: null,
                'players' => $this->players,
                'bank_reference' => $data['bankReference'] ?: null,
                'nic_front_path' => $method === PaymentMethod::PayAtVenue ? auth()->user()->nic_front_path : null,
                'nic_back_path' => $method === PaymentMethod::PayAtVenue ? auth()->user()->nic_back_path : null,
            ];
            if ($this->checkoutMode === 'order') {
                $bookings = app(BookingService::class)->reserveMany(auth()->user(), $this->cartBookingItems(), $method, $details);
                $booking = $bookings->first();
                session()->forget($this->cartSessionKey());
            } else {
                $booking = app(BookingService::class)->reserve(
                    customer: auth()->user(),
                    service: $this->service,
                    option: $this->option(),
                    start: $this->startsAt(),
                    slots: $this->blocks,
                    method: $method,
                    details: $details,
                    games: $this->selectedGames(),
                );
            }
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

        $message = $this->checkoutMode === 'order'
            ? 'Venue order '.$booking->order?->reference.' placed!'
            : 'Booking '.$booking->reference.' placed!';

        return redirect()->route('bookings.show', $booking)->with('message', $message);
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
        return $this->service->venue->allowsPaymentMethod($method)
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
        $cartItems = $this->cartDisplayItems();
        $cartTotal = $cartItems->sum(fn (array $item) => (float) $item['quote']['total']);
        $checkoutQuote = $this->checkoutMode === 'order' && $cartItems->isNotEmpty()
            ? [
                'total' => $cartTotal,
                'lines' => $cartItems->map(fn (array $item) => [
                    'label' => $item['service']->name.' · '.$item['start']->format('D d M, h:i A'),
                    'amount' => $item['quote']['total'],
                ])->all(),
            ]
            : $quote;

        return view('livewire.booking-builder', [
            'option' => $option,
            'days' => $this->upcomingDays(),
            'timeSlots' => $slotsForDay,
            'maxSlots' => $maxSlots,
            'quote' => $quote,
            'checkoutQuote' => $checkoutQuote,
            'cartItems' => $cartItems,
            'cartTotal' => $cartTotal,
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

        if ($this->service->requiresGame() && $this->selectedGames()->count() !== count($this->gameIds)) {
            $this->error = 'Choose games offered by this service.';

            return false;
        }

        if ($this->service->requiresGame() && count($this->gameIds) > $this->service->maxGameSelections($this->blocks)) {
            $maxGames = $this->service->maxGameSelections($this->blocks);
            $this->error = "This session can include up to {$maxGames} ".Str::plural('game', $maxGames).'. Choose a longer session to add more.';

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

    /** @return Collection<int, Game> */
    protected function selectedGames()
    {
        return $this->service->games->whereIn('id', $this->gameIds)->values();
    }

    protected function cartSessionKey(): string
    {
        return 'booking-cart.'.$this->service->venue_id;
    }

    protected function cartItemKey(): string
    {
        return implode(':', [$this->service->id, $this->option()->id, $this->startAt]);
    }

    /** @return array<int, array<string, mixed>> */
    protected function cartEntries(): array
    {
        return collect(session()->get($this->cartSessionKey(), []))
            ->filter(fn ($item) => is_array($item) && isset($item['key'], $item['service_id'], $item['option_id'], $item['start_at'], $item['slots']))
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    protected function cartBookingItems(): array
    {
        $entries = $this->cartEntries();
        if ($entries === []) {
            return [];
        }

        $services = Service::with(['venue.hours', 'venue.owner', 'activityType', 'options', 'games', 'rates'])
            ->whereKey(collect($entries)->pluck('service_id')->unique())
            ->get()
            ->keyBy('id');
        $items = [];

        foreach ($entries as $entry) {
            /** @var Service|null $service */
            $service = $services->get((int) $entry['service_id']);
            $option = $service?->options->firstWhere('id', (int) $entry['option_id']);
            if (! $service || ! $option) {
                continue;
            }

            try {
                $start = Carbon::parse($entry['start_at']);
            } catch (\Throwable) {
                continue;
            }

            $items[] = [
                'service' => $service,
                'option' => $option,
                'start' => $start,
                'slots' => (int) $entry['slots'],
                'players' => isset($entry['players']) ? (int) $entry['players'] : null,
                'games' => array_map('intval', $entry['game_ids'] ?? []),
            ];
        }

        return $items;
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function cartDisplayItems()
    {
        return collect($this->cartBookingItems())->map(function (array $item) {
            $service = $item['service'];
            $option = $item['option'];
            $start = $item['start'];
            $games = $service->games->whereIn('id', $item['games']);

            return [
                'key' => implode(':', [$service->id, $option->id, $start->toDateTimeString()]),
                'service' => $service,
                'option' => $option,
                'start' => $start,
                'end' => $start->copy()->addMinutes($item['slots'] * $service->slot_minutes),
                'games' => $games,
                'quote' => app(PricingService::class)->quote($service, $option, $start, $item['slots']),
            ];
        });
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
