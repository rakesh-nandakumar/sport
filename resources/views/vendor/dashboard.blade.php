@extends('layouts.dashboard')
@section('title', 'Vendor dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <h1 class="display fs-1 mb-0">Dashboard</h1>
        <p class="text-muted mb-0">Everything happening across your venues.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('vendor.venues.create') }}" class="btn btn-danger"><i class="fa-solid fa-plus me-1"></i>Add venue</a>
        <a href="{{ route('vendor.bookings.index') }}" class="btn btn-outline-dark">All bookings</a>
    </div>
</div>

@if($venues->isEmpty())
    <div class="alert alert-warning">You haven't added a venue yet. <a href="{{ route('vendor.venues.create') }}" class="alert-link">Create your first venue</a> to start taking bookings.</div>
@endif

<div class="row g-3 mb-4">
    @foreach([
        ['Today', $stats['today'], 'fa-calendar-day', 'bg-primary'],
        ['This week', $stats['week'], 'fa-calendar-week', 'bg-info'],
        ['Revenue this month', lkr($stats['revenue_month']), 'fa-sack-dollar', 'bg-success'],
        ['Transfers to verify', $stats['pending_verification'], 'fa-money-check', 'bg-warning'],
        ['Unconfirmed holds', $stats['unconfirmed'], 'fa-hourglass-half', 'bg-secondary'],
    ] as [$label, $value, $icon, $bg])
        <div class="col-6 col-lg">
            <div class="card stat-card h-100 shadow-sm border-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="icon {{ $bg }} text-white"><i class="fa-solid {{ $icon }}"></i></span>
                    <div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div></div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold"><i class="fa-regular fa-calendar me-2"></i>Schedule</div>
            <div class="card-body"><div id="calendar"></div></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-semibold"><i class="fa-solid fa-clock me-2"></i>Upcoming bookings</div>
            <div class="list-group list-group-flush">
                @forelse($upcoming as $b)
                    <a href="{{ route('vendor.bookings.show', $b) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $b->service->name }}</strong>
                            <span class="badge {{ $b->status->bsBadge() }}">{{ $b->status->label() }}</span>
                        </div>
                        <div class="small text-muted">{{ $b->starts_at->format('D d M · h:i A') }} · {{ $b->customer_name }} · {{ $b->payment_method->label() }}</div>
                    </a>
                @empty
                    <div class="list-group-item text-muted">No upcoming bookings.</div>
                @endforelse
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold"><i class="fa-solid fa-building me-2"></i>Your venues</div>
            <div class="list-group list-group-flush">
                @foreach($venues as $v)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div><strong>{{ $v->name }}</strong><div class="small text-muted">{{ $v->city }} · {{ $v->services_count }} services · {{ $v->is_approved ? 'Live' : 'Hidden' }}</div></div>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('vendor.venues.services.index', $v) }}" class="btn btn-outline-dark">Services</a>
                            <a href="{{ route('vendor.venues.edit', $v) }}" class="btn btn-outline-dark">Edit</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('head')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: window.innerWidth < 768 ? 'listWeek' : 'timeGridWeek',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'timeGridWeek,timeGridDay,listWeek' },
            slotMinTime: '06:00:00',
            slotMaxTime: '24:00:00',
            height: 620,
            nowIndicator: true,
            events: '{{ route('vendor.events') }}',
        });
        calendar.render();
    });
</script>
@endpush
