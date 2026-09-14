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

@if($vendorStatus !== \App\Enums\VendorStatus::Active)
    <div class="alert {{ $vendorStatus === \App\Enums\VendorStatus::Pending ? 'alert-warning' : 'alert-danger' }}">
        <div class="d-flex align-items-start gap-3">
            <i class="fa-solid {{ $vendorStatus === \App\Enums\VendorStatus::Pending ? 'fa-hourglass-half' : 'fa-ban' }} fs-4 mt-1"></i>
            <div>
                <strong>Account status: {{ $vendorStatus->label() }}.</strong> {{ $vendorStatus->vendorMessage() }}
                @if($profile?->review_notes)<div class="mt-2 border-start border-3 ps-2 small">Note from EntryPoint.lk: {{ $profile->review_notes }}</div>@endif
                <div class="small mt-2">Questions? <a href="mailto:{{ setting('site.support_email') }}" class="alert-link">{{ setting('site.support_email') }}</a> · {{ setting('site.support_phone') }}</div>
            </div>
        </div>
    </div>
@endif

@if($expiringHolds->isNotEmpty())
    <div class="card border-warning shadow-sm mb-4">
        <div class="card-header bg-warning-subtle fw-semibold"><i class="fa-regular fa-clock me-1"></i>Bank transfers waiting for your verification</div>
        <div class="list-group list-group-flush">
            @foreach($expiringHolds as $h)
                @php($left = $h->holdMinutesLeft())
                <a href="{{ route('vendor.bookings.show', $h) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><strong>{{ $h->reference }}</strong> · {{ $h->customer_name }} · {{ $h->service->name }} · {{ $h->starts_at->format('D d M, h:i A') }} · {{ lkr($h->total) }}
                        @if($h->payments->last()?->proof_path)<span class="badge bg-success ms-1">Slip uploaded</span>@else<span class="badge bg-secondary ms-1">No slip yet</span>@endif</span>
                    <span class="badge {{ $left <= 5 ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $left }} min left</span>
                </a>
            @endforeach
        </div>
        <div class="card-footer small text-muted">If you don't verify a transfer within {{ setting('payments.bank_transfer_hold_minutes') }} minutes of the booking, it expires and the slot is released automatically.</div>
    </div>
@endif

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
