@extends('layouts.dashboard')
@section('title', ($service->exists ? 'Edit '.$service->name : 'Add service').' · '.$venue->name)

@section('content')
<nav class="small text-muted mb-1"><a href="{{ route('vendor.venues.index') }}">Venues</a> › <a href="{{ route('vendor.venues.services.index', $venue) }}">{{ $venue->name }}</a> › {{ $service->exists ? $service->name : 'New service' }}</nav>
<h1 class="display fs-1 mb-0">{{ $service->exists ? $service->name : 'Add a service' }}</h1>
<p class="text-muted">Define what's bookable, in what blocks, and at what price. EntryPoint.lk calculates totals automatically.</p>

@php($optionsOld = old('options'))
@php($ratesOld = old('rates'))

<form method="POST" action="{{ $service->exists ? route('vendor.venues.services.update', [$venue, $service]) : route('vendor.venues.services.store', $venue) }}" enctype="multipart/form-data">
    @csrf
    @if($service->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">What is it?</div>
                <div class="card-body row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Activity type</label>
                        <select name="activity_type_id" id="activityType" class="form-select" required>
                            @foreach($activityTypes as $t)
                                <option value="{{ $t->id }}" data-requires-game="{{ $t->requires_game ? 1 : 0 }}" data-slot="{{ $t->default_slot_minutes }}" data-unit="{{ $t->unit_label }}" @selected(old('activity_type_id', $service->activity_type_id) == $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Missing one? Ask an admin to add it under Catalogue → Activity types.</div>
                    </div>
                    <div class="col-md-7"><label class="form-label">Service name</label><input name="name" value="{{ old('name', $service->name) }}" class="form-control" placeholder="e.g. Court A · PS5 Station 1 · Lane 3 · Paintball Session" required></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control" placeholder="Surface, size, equipment included, rules…">{{ old('description', $service->description) }}</textarea></div>
                    <div class="col-md-6"><label class="form-label">Photo</label><input type="file" name="image" accept="image/*" class="form-control">@if($service->image)<img src="{{ $service->imageUrl() }}" class="mt-2 rounded" style="height:70px;object-fit:cover" alt="">@endif</div>
                    <div class="col-md-3"><label class="form-label">Max players</label><input type="number" name="max_players" min="1" value="{{ old('max_players', $service->max_players) }}" class="form-control" placeholder="optional"></div>
                    <div class="col-md-3 d-flex align-items-end"><div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" @checked(old('is_active', $service->is_active))><label class="form-check-label" for="isActive">Accepting bookings</label></div></div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">Booking rules</div>
                <div class="card-body row g-3">
                    <div class="col-md-3"><label class="form-label">Block size (min)</label><input type="number" name="slot_minutes" id="slotMinutes" min="15" step="5" value="{{ old('slot_minutes', $service->slot_minutes) }}" class="form-control" required><div class="form-text">Smallest bookable unit, e.g. 30 or 60.</div></div>
                    <div class="col-md-3"><label class="form-label">Min blocks</label><input type="number" name="min_slots" min="1" value="{{ old('min_slots', $service->min_slots) }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Max blocks</label><input type="number" name="max_slots" min="1" value="{{ old('max_slots', $service->max_slots) }}" class="form-control" placeholder="no limit"></div>
                    <div class="col-md-3"><label class="form-label">Gap between bookings (min)</label><input type="number" name="buffer_minutes" min="0" step="5" value="{{ old('buffer_minutes', $service->buffer_minutes) }}" class="form-control" required><div class="form-text">Cleaning / reset time.</div></div>
                    <div class="col-md-4"><label class="form-label">Minimum notice (min)</label><input type="number" name="lead_time_minutes" min="0" step="15" value="{{ old('lead_time_minutes', $service->lead_time_minutes) }}" class="form-control" required><div class="form-text">How far ahead customers must book.</div></div>
                    <div class="col-md-4"><label class="form-label">Opens at <span class="text-muted">(override)</span></label><input type="time" name="opens_at" value="{{ old('opens_at', $service->opens_at ? substr($service->opens_at, 0, 5) : '') }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Closes at <span class="text-muted">(override)</span></label><input type="time" name="closes_at" value="{{ old('closes_at', $service->closes_at ? substr($service->closes_at, 0, 5) : '') }}" class="form-control"><div class="form-text">Leave blank to use venue hours.</div></div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Options &amp; pricing <span class="text-muted fw-normal small">— seat types, court sizes, packages</span></span>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="addRow('options')"><i class="fa-solid fa-plus me-1"></i>Add option</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" id="options-table">
                            <thead><tr><th>Default</th><th>Name</th><th>Description</th><th>Price / block (Rs)</th><th>Units</th><th></th></tr></thead>
                            <tbody>
                            @php($optionRows = $optionsOld ?: $options->map(fn ($o) => ['id' => $o->id, 'name' => $o->name, 'description' => $o->description, 'price_per_slot' => $o->price_per_slot, 'capacity' => $o->capacity, 'is_default' => $o->is_default])->all())
                            @foreach($optionRows as $i => $row)
                                <tr>
                                    <td><input type="radio" name="default_option" value="{{ $i }}" class="form-check-input" @checked(old('default_option', collect($optionRows)->search(fn ($r) => ! empty($r['is_default'])) ?: 0) == $i)></td>
                                    <td><input type="hidden" name="options[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}"><input name="options[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" class="form-control form-control-sm" placeholder="Standard" required></td>
                                    <td><input name="options[{{ $i }}][description]" value="{{ $row['description'] ?? '' }}" class="form-control form-control-sm" placeholder="optional"></td>
                                    <td><input type="number" name="options[{{ $i }}][price_per_slot]" value="{{ $row['price_per_slot'] ?? '' }}" min="0" step="50" class="form-control form-control-sm" required></td>
                                    <td><input type="number" name="options[{{ $i }}][capacity]" value="{{ $row['capacity'] ?? 1 }}" min="1" class="form-control form-control-sm" style="width:80px" required></td>
                                    <td><button type="button" class="btn btn-sm btn-link text-danger" onclick="removeRow(this)"><i class="fa-solid fa-xmark"></i></button></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="form-text mt-2">"Units" is how many identical things exist — 3 PS5 stations under one "Standard seat" option means 3 customers can book the same time.</div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Peak-hour rates <span class="text-muted fw-normal small">— optional multipliers by day &amp; time</span></span>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="addRow('rates')"><i class="fa-solid fa-plus me-1"></i>Add rate</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" id="rates-table">
                            <thead><tr><th>Name</th><th>Days</th><th>From</th><th>To</th><th>Multiplier</th><th></th></tr></thead>
                            <tbody>
                            @php($rateRows = $ratesOld ?: $rates->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'days' => $r->days, 'starts_at' => substr($r->starts_at, 0, 5), 'ends_at' => substr($r->ends_at, 0, 5), 'multiplier' => $r->multiplier])->all())
                            @foreach($rateRows as $i => $row)
                                <tr>
                                    <td><input type="hidden" name="rates[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}"><input name="rates[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" class="form-control form-control-sm" placeholder="Weekend evenings"></td>
                                    <td class="text-nowrap">@foreach(['S','M','T','W','T','F','S'] as $d => $l)<label class="me-1 small"><input type="checkbox" name="rates[{{ $i }}][days][]" value="{{ $d }}" @checked(in_array($d, $row['days'] ?? []))> {{ $l }}</label>@endforeach</td>
                                    <td><input type="time" name="rates[{{ $i }}][starts_at]" value="{{ $row['starts_at'] ?? '' }}" class="form-control form-control-sm"></td>
                                    <td><input type="time" name="rates[{{ $i }}][ends_at]" value="{{ $row['ends_at'] ?? '' }}" class="form-control form-control-sm"></td>
                                    <td><input type="number" name="rates[{{ $i }}][multiplier]" value="{{ $row['multiplier'] ?? '1.25' }}" step="0.05" min="0.1" max="5" class="form-control form-control-sm" style="width:90px"></td>
                                    <td><button type="button" class="btn btn-sm btn-link text-danger" onclick="removeRow(this)"><i class="fa-solid fa-xmark"></i></button></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="form-text mt-2">1.25 = 25% more than the option price during those hours; 0.8 = 20% off-peak discount.</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4" id="gamesCard">
                <div class="card-header bg-white fw-semibold">Games available <span class="text-muted fw-normal small">— for gaming activities</span></div>
                <div class="card-body" style="max-height:420px;overflow:auto">
                    @foreach($games as $typeId => $list)
                        <div class="game-group" data-type="{{ $typeId }}">
                            <div class="small text-muted text-uppercase fw-semibold mb-1">{{ $list->first()->activityType->name }}</div>
                            @foreach($list as $g)
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="games[]" value="{{ $g->id }}" id="g{{ $g->id }}" @checked(in_array($g->id, old('games', $selectedGames)))><label class="form-check-label small" for="g{{ $g->id }}">{{ $g->name }} <span class="text-muted">{{ $g->platform }}</span></label></div>
                            @endforeach
                        </div>
                    @endforeach
                    <p class="small text-muted mb-0" id="gamesEmpty">This activity type doesn't use game selection.</p>
                </div>
            </div>
            <button class="btn btn-danger w-100 py-2">{{ $service->exists ? 'Save service' : 'Create service' }}</button>
            <a href="{{ route('vendor.venues.services.index', $venue) }}" class="btn btn-link w-100 text-muted">Cancel</a>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function addRow(kind) {
        const tbody = document.querySelector('#' + kind + '-table tbody');
        const i = tbody.querySelectorAll('tr').length;
        const days = ['S','M','T','W','T','F','S'].map((l, d) => `<label class="me-1 small"><input type="checkbox" name="rates[${i}][days][]" value="${d}"> ${l}</label>`).join('');
        const html = kind === 'options'
            ? `<tr><td><input type="radio" name="default_option" value="${i}" class="form-check-input"></td>
               <td><input type="hidden" name="options[${i}][id]"><input name="options[${i}][name]" class="form-control form-control-sm" placeholder="VIP seat" required></td>
               <td><input name="options[${i}][description]" class="form-control form-control-sm" placeholder="optional"></td>
               <td><input type="number" name="options[${i}][price_per_slot]" min="0" step="50" class="form-control form-control-sm" required></td>
               <td><input type="number" name="options[${i}][capacity]" value="1" min="1" class="form-control form-control-sm" style="width:80px" required></td>
               <td><button type="button" class="btn btn-sm btn-link text-danger" onclick="removeRow(this)"><i class="fa-solid fa-xmark"></i></button></td></tr>`
            : `<tr><td><input type="hidden" name="rates[${i}][id]"><input name="rates[${i}][name]" class="form-control form-control-sm" placeholder="Weekend evenings"></td>
               <td class="text-nowrap">${days}</td>
               <td><input type="time" name="rates[${i}][starts_at]" class="form-control form-control-sm"></td>
               <td><input type="time" name="rates[${i}][ends_at]" class="form-control form-control-sm"></td>
               <td><input type="number" name="rates[${i}][multiplier]" value="1.25" step="0.05" min="0.1" max="5" class="form-control form-control-sm" style="width:90px"></td>
               <td><button type="button" class="btn btn-sm btn-link text-danger" onclick="removeRow(this)"><i class="fa-solid fa-xmark"></i></button></td></tr>`;
        tbody.insertAdjacentHTML('beforeend', html);
    }
    function removeRow(btn) { btn.closest('tr').remove(); }

    const typeSelect = document.getElementById('activityType');
    function syncGames() {
        const opt = typeSelect.selectedOptions[0];
        const requires = opt.dataset.requiresGame === '1';
        let any = false;
        document.querySelectorAll('.game-group').forEach(g => {
            const show = requires && g.dataset.type === opt.value;
            g.style.display = show ? '' : 'none';
            if (show) any = true;
        });
        document.getElementById('gamesEmpty').style.display = any ? 'none' : '';
    }
    typeSelect.addEventListener('change', () => {
        syncGames();
        const slot = document.getElementById('slotMinutes');
        if (!slot.dataset.touched) slot.value = typeSelect.selectedOptions[0].dataset.slot;
    });
    document.getElementById('slotMinutes').addEventListener('input', e => e.target.dataset.touched = '1');
    syncGames();
</script>
@endpush
