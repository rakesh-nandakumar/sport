<?php

namespace App\Livewire;

use App\Models\Indoor;
use App\Models\Resource;
use App\Services\BookingService;
use Carbon\Carbon;
use Livewire\Component;

class BookingWidget extends Component
{
    public Indoor $indoor;

    public ?int $resource_id = null;
    public ?string $start_time = null;
    public ?string $finish_time = null;
    public int $unit_quantity = 1;
    public array $selectedOptions = [];

    public function mount(Indoor $indoor): void
    {
        $this->indoor = $indoor;
    }

    public function updatedResourceId(): void
    {
        $resource = $this->selectedResource();

        if (! $resource) {
            return;
        }

        $this->start_time = null;
        $this->finish_time = null;
        $this->unit_quantity = 1;
        $this->selectedOptions = [];

        $this->dispatch('resource-changed', [
            'url' => route('resource.availability', $resource),
            'slotMin' => $this->slotMin($resource),
            'slotMax' => $this->slotMax($resource),
        ]);
    }

    public function total(): ?float
    {
        $resource = $this->selectedResource();

        if (! $resource || ! $this->start_time || ! $this->finish_time) {
            return null;
        }

        try {
            return app(BookingService::class)->calculateTotal(
                $resource,
                Carbon::parse($this->start_time),
                Carbon::parse($this->finish_time),
                $this->unit_quantity
            );
        } catch (\Throwable) {
            return null;
        }
    }

    public function selectedResource(): ?Resource
    {
        return $this->resource_id ? Resource::with('activity')->find($this->resource_id) : null;
    }

    /** Bookable list fields (e.g. game library) with their options, for the selected resource. */
    public function bookableOptions(): array
    {
        return $this->selectedResource()?->bookableOptions() ?? [];
    }

    /** Earliest opening time across the week, for the calendar's slotMinTime. */
    public function slotMin(Resource $resource): string
    {
        return $this->extremeTime($resource, 'open', min: true);
    }

    /** Latest closing time across the week, for the calendar's slotMaxTime. */
    public function slotMax(Resource $resource): string
    {
        return $this->extremeTime($resource, 'close', min: false);
    }

    public function render()
    {
        return view('livewire.booking-widget', [
            'resources' => $this->indoor->resources()
                ->with('activity')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->groupBy(fn ($resource) => $resource->activity?->name ?? 'Other'),
            'pricingUnits' => config('activities.pricing_units'),
        ]);
    }

    private function extremeTime(Resource $resource, string $key, bool $min): string
    {
        $minutes = null;

        for ($i = 0; $i < 7; $i++) {
            $hours = $resource->hoursFor(now()->addDays($i));

            if ($hours === null) {
                continue;
            }

            [$h, $m] = array_pad(explode(':', $hours[$key]), 2, '0');
            $value = ((int) $h) * 60 + (int) $m;

            $minutes = $min
                ? min($minutes ?? $value, $value)
                : max($minutes ?? $value, $value);
        }

        if ($minutes === null) {
            return $min ? '8:00:00' : '23:59:59';
        }

        return sprintf('%02d:%02d:%02d', intdiv($minutes, 60), $minutes % 60, intval(fmod($minutes, 1)));
    }
}
