@extends('layouts.dashboard')
@section('title', 'Services · '.$venue->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <nav class="small text-muted"><a href="{{ route('vendor.venues.index') }}">Venues</a> › {{ $venue->name }}</nav>
        <h1 class="display fs-1 mb-0">Services</h1>
        <p class="text-muted mb-0">Anything a customer can book at {{ $venue->name }} — courts, stations, lanes, sessions.</p>
    </div>
    <a href="{{ route('vendor.venues.services.create', $venue) }}" class="btn btn-danger"><i class="fa-solid fa-plus me-1"></i>Add service</a>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Service</th><th>Activity</th><th>Block</th><th>Rules</th><th>Options</th><th>Bookings</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($services as $s)
                <tr>
                    <td><strong>{{ $s->name }}</strong>@if($s->games->count())<div class="small text-muted"><i class="fa-solid fa-gamepad me-1"></i>{{ $s->games->count() }} games</div>@endif</td>
                    <td><span class="badge" style="background: {{ $s->activityType->color }}"><i class="{{ $s->activityType->icon }} me-1"></i>{{ $s->activityType->name }}</span></td>
                    <td>{{ $s->slotLabel() }}</td>
                    <td class="small text-muted">min {{ $s->min_slots }}@if($s->max_slots)–{{ $s->max_slots }}@endif blocks<br>{{ $s->buffer_minutes }} min gap · {{ $s->lead_time_minutes }} min notice</td>
                    <td class="small">@foreach($s->options as $o)<div>{{ $o->name }} · {{ lkr($o->price_per_slot) }} × {{ $o->capacity }}</div>@endforeach</td>
                    <td>{{ $s->bookings_count }}</td>
                    <td><span class="badge {{ $s->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $s->is_active ? 'Active' : 'Paused' }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('booking.build', $s) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Preview"><i class="fa-solid fa-eye"></i></a>
                        <a href="{{ route('vendor.venues.services.edit', [$venue, $s]) }}" class="btn btn-sm btn-outline-dark">Edit</a>
                        <form method="POST" action="{{ route('vendor.venues.services.destroy', [$venue, $s]) }}" class="d-inline" onsubmit="return confirm('Remove this service?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-5">No services yet. <a href="{{ route('vendor.venues.services.create', $venue) }}">Add your first service</a>.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
