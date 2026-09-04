<?php

namespace App\Livewire;

use App\Http\Requests\ResourceRequest;
use App\Models\Activity;
use App\Models\Indoor;
use App\Models\Resource;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ManageResources extends Component
{
    public Indoor $indoor;

    public array $resources = [];
    public bool $editing = false;
    public ?int $editingId = null;

    public string $name = '';
    public ?int $activity_id = null;
    public string $description = '';
    public ?int $capacity = null;
    public ?string $rate = null;
    public string $pricing_unit = 'per_hour';
    public int $min_duration_minutes = 60;
    public int $slot_increment_minutes = 30;
    public bool $is_active = true;
    public array $customFields = [];

    public function mount(Indoor $indoor): void
    {
        if ($indoor->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action');
        }

        $this->indoor = $indoor;
        $this->loadResources();
    }

    public function updatedActivityId(): void
    {
        $activity = $this->currentActivity();

        if (! $activity) {
            return;
        }

        $this->pricing_unit = $activity->default_pricing_unit;

        if ($this->capacity === null) {
            $this->capacity = $activity->defaultCapacity();
        }
    }

    public function addListItem(string $field): void
    {
        $this->customFields[$field][] = '';
    }

    public function removeListItem(string $field, int $index): void
    {
        unset($this->customFields[$field][$index]);
        $this->customFields[$field] = array_values($this->customFields[$field] ?? []);
    }

    public function edit(int $id): void
    {
        $resource = $this->indoor->resources()->with('activity')->findOrFail($id);

        $this->editing = true;
        $this->editingId = $resource->id;
        $this->name = $resource->name;
        $this->activity_id = $resource->activity_id;
        $this->description = $resource->description ?? '';
        $this->capacity = $resource->capacity;
        $this->rate = $resource->rate;
        $this->pricing_unit = $resource->pricing_unit;
        $this->min_duration_minutes = $resource->min_duration_minutes ?? 60;
        $this->slot_increment_minutes = $resource->slot_increment_minutes ?? 30;
        $this->is_active = $resource->is_active;
        $this->customFields = $resource->custom_fields ?? [];
    }

    public function delete(int $id): void
    {
        $resource = $this->indoor->resources()->findOrFail($id);

        $resource->delete();

        $this->resetForm();
        $this->loadResources();

        session()->flash('message', 'Resource deleted successfully!');
    }

    public function resetForm(): void
    {
        $this->editing = false;
        $this->editingId = null;
        $this->name = '';
        $this->activity_id = null;
        $this->description = '';
        $this->capacity = null;
        $this->rate = null;
        $this->pricing_unit = 'per_hour';
        $this->min_duration_minutes = 60;
        $this->slot_increment_minutes = 30;
        $this->is_active = true;
        $this->customFields = [];
    }

    public function save(): void
    {
        $activity = $this->currentActivity() ?? Activity::where('slug', 'other')->first() ?? Activity::first();

        if (! $activity) {
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'activity_id' => ['required', 'integer', 'exists:activities,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'rate' => ['required', 'numeric', 'min:0'],
            'pricing_unit' => ['required', Rule::in(array_keys(config('activities.pricing_units')))],
            'min_duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'slot_increment_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'is_active' => ['boolean'],
            ...ResourceRequest::dynamicRules($activity),
        ]);

        $data = [
            'indoor_id' => $this->indoor->id,
            'activity_id' => $activity->id,
            'name' => $this->name,
            'description' => $this->description !== '' ? $this->description : null,
            'capacity' => $this->capacity,
            'rate' => $this->rate,
            'pricing_unit' => $this->pricing_unit,
            'min_duration_minutes' => $this->min_duration_minutes,
            'slot_increment_minutes' => $this->slot_increment_minutes,
            'is_active' => (bool) $this->is_active,
            'custom_fields' => $this->buildCustomFields($activity),
        ];

        if ($this->editingId) {
            $this->indoor->resources()->findOrFail($this->editingId)->update($data);
        } else {
            $this->indoor->resources()->create($data);
        }

        $this->resetForm();
        $this->loadResources();

        session()->flash('message', 'Resource saved successfully!');
    }

    public function render()
    {
        return view('livewire.manage-resources', [
            'activities' => Activity::orderBy('sort_order')->get(),
            'pricingUnits' => config('activities.pricing_units'),
        ]);
    }

    public function currentActivity(): ?Activity
    {
        return $this->activity_id ? Activity::find($this->activity_id) : null;
    }

    private function loadResources(): void
    {
        $this->resources = $this->indoor->resources()
            ->with('activity')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Resource $resource) => [
                'id' => $resource->id,
                'name' => $resource->name,
                'description' => $resource->description,
                'capacity' => $resource->capacity,
                'rate' => $resource->rate,
                'pricing_unit' => $resource->pricing_unit,
                'custom_fields' => $resource->custom_fields ?? [],
                'is_active' => $resource->is_active,
                'activity' => $resource->activity?->name,
                'activity_slug' => $resource->activity?->slug,
            ])
            ->all();
    }

    private function buildCustomFields(Activity $activity): array
    {
        $customFields = [];

        foreach ($activity->fields() as $key => $def) {
            $value = $this->customFields[$key] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $customFields[$key] = match ($def['type'] ?? 'text') {
                'boolean' => (bool) $value,
                'number' => (int) $value,
                'list' => array_values(array_filter(array_map('trim', (array) $value), fn ($v) => $v !== '')),
                default => (string) $value,
            };
        }

        return $customFields;
    }
}
