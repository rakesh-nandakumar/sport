<div>
    @php
        $selectedResource = $this->selectedResource();
        $bookableFields = $this->bookableOptions();
        $total = $this->total();
    @endphp

    <div class="mx-auto max-w-6xl px-6">
        <span class="eyebrow">Check availability</span>
        <h2 class="text-2xl font-bold text-gray-900">Book Time Slots</h2>

        <div class="mt-6 overflow-hidden rounded-2xl border border-gray-100 bg-white p-2 shadow-sm sm:p-4">
            <div id="booking-calendar"></div>
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-6 pb-4 pt-10">
        <form method="POST" action="/home/{{ $this->indoor->id }}/book" enctype="multipart/form-data">
            @csrf

            <div>
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-8 lg:grid-cols-3">
                    <div class="text-gray-600">
                        <p class="text-lg font-semibold text-gray-900">Personal Details</p>
                        <p class="mt-1 text-sm">Please fill out all the fields.</p>

                        <div class="mt-4">
                            @if ($total !== null)
                                <div class="block w-full rounded-xl bg-gradient-to-br from-brand-600 to-brand-800 py-3 text-center text-sm font-semibold text-white shadow-md">
                                    Total: Rs {{ number_format($total, 2) }}
                                </div>
                            @else
                                <div class="block w-full rounded-xl border border-dashed border-gray-200 bg-gray-50 py-3 text-center text-sm font-semibold text-gray-400">
                                    Select a unit to see the price
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="field md:col-span-2">
                                <label for="resource_id" class="field-label">Bookable unit</label>
                                <select wire:model.live="resource_id" id="resource_id" class="field-input">
                                    <option value="">Choose a unit...</option>
                                    @foreach ($resources as $activityName => $activityResources)
                                        <optgroup label="{{ $activityName }}">
                                            @foreach ($activityResources as $resourceOption)
                                                <option value="{{ $resourceOption->id }}">
                                                    {{ $resourceOption->name }} -
                                                    Rs {{ number_format($resourceOption->rate, 2) }}
                                                    {{ $pricingUnits[$resourceOption->pricing_unit]['label'] ?? $resourceOption->pricing_unit }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>

                            @if ($selectedResource && $selectedResource->activity)
                                @foreach ($bookableFields as $fieldKey => $field)
                                    <div class="field md:col-span-2">
                                        <label for="option_{{ $fieldKey }}" class="field-label">{{ $field['label'] }}</label>
                                        <select wire:model="selectedOptions.{{ $fieldKey }}" id="option_{{ $fieldKey }}" class="field-input">
                                            <option value="">{{ $field['select_label'] ?? 'Choose...' }}</option>
                                            @foreach ($field['values'] as $value)
                                                <option value="{{ $value }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                        @if (($this->selectedOptions[$fieldKey] ?? '') !== '')
                                            <input type="hidden" name="selected_options[{{ $fieldKey }}]"
                                                   value="{{ $this->selectedOptions[$fieldKey] }}" />
                                        @endif
                                    </div>
                                @endforeach
                            @endif

                            @if ($selectedResource && ! ($pricingUnits[$selectedResource->pricing_unit]['time_based'] ?? true))
                                <div class="field md:col-span-2">
                                    <label for="unit_quantity" class="field-label">
                                        {{ $pricingUnits[$selectedResource->pricing_unit]['quantity_label'] ?? 'Quantity' }}
                                    </label>
                                    <input type="number" wire:model.live="unit_quantity" id="unit_quantity" min="1"
                                           class="field-input" />
                                </div>
                            @endif

                            <div class="field">
                                <label for="full_name" class="field-label">Full Name</label>
                                <input type="text" name="custName" id="full_name"
                                       class="field-input"
                                       value="{{ auth()->user()->name }}" />
                            </div>

                            <div class="field">
                                <label for="phone" class="field-label">Phone Number</label>
                                <input type="text" name="phoneNumber" id="phone"
                                       class="field-input" />
                            </div>

                            <div class="field">
                                <label for="start_time" class="field-label">Start Time</label>
                                <input type="datetime-local" wire:model.live="start_time" name="start_time" id="start_time"
                                       @if ($selectedResource) step="{{ $selectedResource->slot_increment_minutes }}" @endif
                                       class="field-input" />
                            </div>

                            <div class="field">
                                <label for="finish_time" class="field-label">End Time</label>
                                <input type="datetime-local" wire:model.live="finish_time" name="finish_time" id="finish_time"
                                       @if ($selectedResource) step="{{ $selectedResource->slot_increment_minutes }}" @endif
                                       class="field-input" />
                            </div>

                            <input type="hidden" name="resource_id" value="{{ $this->resource_id }}" />
                            <input type="hidden" name="unit_quantity" value="{{ $this->unit_quantity ?: 1 }}" />

                            <div class="md:col-span-2 text-right">
                                <button class="btn btn-primary">Confirm Booking</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        (function () {
            var calendarEl = document.getElementById('booking-calendar');
            if (!calendarEl) return;

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                selectable: false,
                slotMinTime: '8:00:00',
                slotMaxTime: '23:59:59',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridWeek,timeGridDay'
                },
                events: []
            });

            calendar.render();

            Livewire.on('resource-changed', function (data) {
                fetch(data.url)
                    .then(function (response) { return response.json(); })
                    .then(function (events) {
                        calendar.removeAllEvents();
                        calendar.addEventSource(events);
                        if (data.slotMin) calendar.setOption('slotMinTime', data.slotMin);
                        if (data.slotMax) calendar.setOption('slotMaxTime', data.slotMax);
                    });
            });
        })();
    </script>
</div>
