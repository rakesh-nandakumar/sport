<?php

namespace App\Livewire;

use App\Http\Controllers\CheckoutController;
use App\Models\Service;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use Carbon\Carbon;
use Livewire\Component;

class BookingBuilder extends Component
{
    public Service $service;

    public int $optionId;

    public ?int $gameId = null;

    public string $date;

    public ?string $startTime = null;

    public int $blocks = 1;

    public ?int $players = null;

    public string $error = '';

    public function mount(Service $service): void
    {
        $this->service = $service->load(['venue.hours', 'activityType', 'options', 'games', 'rates']);
        $this->optionId = $service->defaultOption()->id;
        $this->blocks = max(1, $service->min_slots);
        $this->date = $this->firstOpenDate()->toDateString();
    }

    public function updatedOptionId(): void
    {
        $this->startTime = null;
        $this->error = '';
    }

    public function updatedDate(): void
    {
        $this->startTime = null;
        $this->error = '';
    }

    public function selectDate(string $date): void
    {
        $this->date = $date;
        $this->updatedDate();
    }

    public function selectTime(string $time): void
    {
        $this->startTime = $time;
        $this->error = '';
        $max = $this->maxSlots();
        if ($max > 0 && $this->blocks > $max) {
            $this->blocks = $max;
        }
    }

    public function incrementSlots(): void
    {
        $max = $this->maxSlots();
        if ($this->blocks < $max) {
            $this->blocks++;
        }
    }

    public function decrementSlots(): void
    {
        if ($this->blocks > $this->service->min_slots) {
            $this->blocks--;
        }
    }

    public function checkout()
    {
        if (! auth()->check()) {
            session()->put('url.intended', route('booking.build', $this->service));

            return redirect()->route('login')->with('message', 'Log in or create a free account to finish your booking.');
        }

        if (! $this->startTime) {
            $this->error = 'Pick a start time to continue.';

            return;
        }

        if ($this->service->requiresGame() && ! $this->gameId) {
            $this->error = 'Choose the game you want to play.';

            return;
        }

        $max = $this->maxSlots();
        if ($this->blocks < $this->service->min_slots || $this->blocks > $max) {
            $this->error = $max === 0
                ? 'That start time is no longer available.'
                : "You can book between {$this->service->min_slots} and {$max} blocks from this start time.";

            return;
        }

        session()->put(CheckoutController::SESSION_KEY, [
            'service_id' => $this->service->id,
            'option_id' => $this->optionId,
            'game_id' => $this->gameId,
            'starts_at' => $this->startsAt()->toDateTimeString(),
            'slots' => $this->blocks,
            'players' => $this->players,
        ]);

        return redirect()->route('checkout.show');
    }

    public function render()
    {
        $availability = app(AvailabilityService::class);
        $option = $this->option();
        $day = Carbon::parse($this->date);
        $slotsForDay = $availability->slotsForDay($this->service, $option, $day);
        $maxSlots = $this->maxSlots();
        $quote = $this->startTime
            ? app(PricingService::class)->quote($this->service, $option, $this->startsAt(), $this->blocks)
            : null;

        return view('livewire.booking-builder', [
            'option' => $option,
            'days' => $this->upcomingDays(),
            'timeSlots' => $slotsForDay,
            'maxSlots' => $maxSlots,
            'quote' => $quote,
            'endsAt' => $this->startTime ? $this->startsAt()->addMinutes($this->blocks * $this->service->slot_minutes) : null,
            'window' => $this->service->windowFor($day->dayOfWeek),
        ]);
    }

    protected function option()
    {
        return $this->service->options->firstWhere('id', $this->optionId) ?? $this->service->defaultOption();
    }

    protected function startsAt(): Carbon
    {
        return Carbon::parse($this->date.' '.$this->startTime);
    }

    protected function maxSlots(): int
    {
        if (! $this->startTime) {
            return $this->service->max_slots ?? 12;
        }

        return app(AvailabilityService::class)->maxSlotsFrom($this->service, $this->option(), $this->startsAt());
    }

    /** Next 14 days as chips; days the venue is closed are flagged. */
    protected function upcomingDays(): array
    {
        return collect(range(0, 13))->map(function ($i) {
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
        for ($i = 0; $i < 14; $i++) {
            $d = today()->addDays($i);
            if ($this->service->windowFor($d->dayOfWeek)) {
                return $d;
            }
        }

        return today();
    }
}
