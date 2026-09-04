<div>
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Bookable units</h3>

        @if (session('message'))
            <p class="mb-4 rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-700">{{ session('message') }}</p>
        @endif

        <div class="space-y-3">
            @forelse ($this->resources as $resource)
                <div class="flex items-start justify-between rounded-xl border border-gray-200 p-4">
                    <div>
                        <p class="font-medium text-gray-900">
                            {{ $resource['name'] }}
                            @unless ($resource['is_active'])
                                <span class="ml-2 text-xs text-gray-500">(inactive)</span>
                            @endunless
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ $resource['activity'] }} -
                            Rs {{ number_format($resource['rate'], 2) }} /
                            {{ $pricingUnits[$resource['pricing_unit']]['label'] ?? $resource['pricing_unit'] }}
                            @if ($resource['capacity'])
                                ({{ $resource['capacity'] }} capacity)
                            @endif
                        </p>
                        @isset($resource['custom_fields']['game_library'])
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach ($resource['custom_fields']['game_library'] as $game)
                                    <span class="rounded-full bg-brand-50 px-3 py-1 text-xs text-brand-700">{{ $game }}</span>
                                @endforeach
                            </div>
                        @endisset
                        @if ($resource['description'])
                            <p class="mt-2 text-sm text-gray-500">{{ $resource['description'] }}</p>
                        @endif
                    </div>
                    <div class="flex flex-shrink-0 space-x-2">
                        <button type="button" wire:click="edit({{ $resource['id'] }})"
                                class="rounded-lg px-2.5 py-1 text-sm font-medium text-brand-600 hover:bg-brand-50">Edit</button>
                        <button type="button" wire:click="delete({{ $resource['id'] }})"
                                wire:confirm="Delete this unit?"
                                class="rounded-lg px-2.5 py-1 text-sm font-medium text-red-500 hover:bg-red-50">Delete</button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No bookable units yet. Add one below.</p>
            @endforelse
        </div>
    </div>

    <form wire:submit="save" class="space-y-4 rounded-2xl border border-gray-200 bg-gray-50 p-4">
        <div class="flex items-center justify-between">
            <h4 class="font-semibold text-gray-900">{{ $editing ? 'Edit' : 'Add' }} bookable unit</h4>
            <button type="button" wire:click="resetForm" class="text-sm text-gray-500 hover:text-gray-800">Clear</button>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="resource-name" class="mb-2 block text-sm font-medium text-gray-700">Name</label>
                <input id="resource-name" type="text" wire:model="name"
                       class="w-full rounded-lg border border-gray-200 p-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100" placeholder="Example: Court A" />
                @error('name')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="resource-activity" class="mb-2 block text-sm font-medium text-gray-700">Activity type</label>
                <select id="resource-activity" wire:model.live="activity_id"
                        class="w-full rounded-lg border border-gray-200 p-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                    <option value="">Choose an activity</option>
                    @foreach ($activities as $activity)
                        <option value="{{ $activity->id }}">{{ $activity->name }}</option>
                    @endforeach
                </select>
                @error('activity_id')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        @if ($activity = $this->currentActivity())
            <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-4">
                <h5 class="text-sm font-semibold text-gray-700">{{ $activity->name }} fields</h5>
                @foreach ($activity->fields() as $fieldKey => $field)
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">{{ $field['label'] ?? ucfirst($fieldKey) }}</label>

                        @if (($field['type'] ?? 'text') === 'select')
                            <select wire:model="customFields.{{ $fieldKey }}"
                                    class="w-full rounded-lg border border-gray-200 p-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                                <option value="">Select...</option>
                                @foreach ($field['options'] ?? [] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        @elseif (($field['type'] ?? 'text') === 'number')
                            <input type="number" wire:model="customFields.{{ $fieldKey }}"
                                   @isset($field['min']) min="{{ $field['min'] }}" @endisset
                                   @isset($field['max']) max="{{ $field['max'] }}" @endisset
                                   class="w-full rounded-lg border border-gray-200 p-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100" />
                        @elseif (($field['type'] ?? 'text') === 'boolean')
                            <input type="checkbox" wire:model="customFields.{{ $fieldKey }}"
                                   class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                        @elseif (($field['type'] ?? 'text') === 'list')
                            <div class="space-y-2">
                                @foreach ($this->customFields[$fieldKey] ?? [] as $index => $item)
                                    <div class="flex gap-2">
                                        <input type="text" wire:model="customFields.{{ $fieldKey }}.{{ $index }}"
                                               class="w-full rounded-lg border border-gray-200 p-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                                               placeholder="{{ $field['placeholder'] ?? '' }}" />
                                        <button type="button" wire:click="removeListItem('{{ $fieldKey }}', {{ $index }})"
                                                class="px-3 text-red-500 hover:text-red-700">Remove</button>
                                    </div>
                                @endforeach
                                <button type="button" wire:click="addListItem('{{ $fieldKey }}')"
                                        class="text-sm font-medium text-brand-600 hover:text-brand-700">+ Add item</button>
                            </div>
                        @else
                            <input type="text" wire:model="customFields.{{ $fieldKey }}"
                                   class="w-full rounded-lg border border-gray-200 p-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                                   placeholder="{{ $field['placeholder'] ?? '' }}" />
                        @endif

                        @error("custom_fields.{$fieldKey}")
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="resource-rate" class="mb-2 block text-sm font-medium text-gray-700">Rate (Rs)</label>
                <input id="resource-rate" type="number" step="0.01" wire:model="rate"
                       class="w-full rounded-lg border border-gray-200 p-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100" min="0" />
                @error('rate')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="resource-pricing" class="mb-2 block text-sm font-medium text-gray-700">Pricing unit</label>
                <select id="resource-pricing" wire:model="pricing_unit"
                        class="w-full rounded-lg border border-gray-200 p-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                    @foreach ($pricingUnits as $unitKey => $unit)
                        <option value="{{ $unitKey }}">{{ $unit['label'] }}</option>
                    @endforeach
                </select>
                @error('pricing_unit')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="resource-capacity" class="mb-2 block text-sm font-medium text-gray-700">Capacity (players / controllers / seats)</label>
                <input id="resource-capacity" type="number" wire:model="capacity"
                       class="w-full rounded-lg border border-gray-200 p-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100" min="1" />
                @error('capacity')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="resource-min-duration" class="mb-2 block text-sm font-medium text-gray-700">Minimum duration (minutes)</label>
                <input id="resource-min-duration" type="number" wire:model="min_duration_minutes"
                       class="w-full rounded-lg border border-gray-200 p-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100" min="15" max="1440" />
                @error('min_duration_minutes')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="resource-increment" class="mb-2 block text-sm font-medium text-gray-700">Slot increment (minutes)</label>
                <input id="resource-increment" type="number" wire:model="slot_increment_minutes"
                       class="w-full rounded-lg border border-gray-200 p-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100" min="5" max="1440" />
                @error('slot_increment_minutes')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Active</label>
                <input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="rounded-lg bg-brand-600 px-5 py-2.5 font-semibold text-white transition-colors hover:bg-brand-700">
                {{ $editing ? 'Update' : 'Add' }} unit
            </button>
        </div>
    </form>
</div>
