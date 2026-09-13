@extends('layouts.dashboard')
@section('title', 'Admin dashboard')

@section('content')
<h1 class="display fs-1 mb-0">Platform overview</h1>
<p class="text-muted">Venues, bookings and growth across Sportee.</p>

<div class="row g-3 mb-4">
    @foreach([
        ['Venues', $stats['venues'], 'fa-building', 'bg-primary', $stats['pending_venues'].' hidden'],
        ['Vendors', $stats['vendors'], 'fa-store', 'bg-info', null],
        ['Customers', $stats['customers'], 'fa-users', 'bg-secondary', null],
        ['Bookings', $stats['bookings'], 'fa-calendar-check', 'bg-dark', $stats['bookings_today'].' today · '.$stats['bookings_month'].' this month'],
        ['Paid revenue (month)', lkr($stats['revenue_month']), 'fa-sack-dollar', 'bg-success', null],
    ] as [$label, $value, $icon, $bg, $sub])
        <div class="col-6 col-lg">
            <div class="card stat-card h-100 shadow-sm border-0"><div class="card-body d-flex align-items-center gap-3">
                <span class="icon {{ $bg }} text-white"><i class="fa-solid {{ $icon }}"></i></span>
                <div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div>@if($sub)<div class="small text-muted">{{ $sub }}</div>@endif</div>
            </div></div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4"><div class="card-header bg-white fw-semibold">Bookings — last 30 days</div><div class="card-body"><canvas id="bookingsChart" height="110"></canvas></div></div>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">Latest bookings</div>
            <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Ref</th><th>Venue</th><th>Service</th><th>Customer</th><th>When</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                @foreach($recent as $b)
                    <tr><td class="font-monospace small">{{ $b->reference }}</td><td>{{ $b->venue->name }}</td><td>{{ $b->service->name }}</td><td>{{ $b->user->name }}</td><td class="small">{{ $b->starts_at->format('d M, h:i A') }}</td><td><span class="badge {{ $b->status->bsBadge() }}">{{ $b->status->label() }}</span></td><td class="text-end">{{ lkr($b->total) }}</td></tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Bookings by activity</div><div class="card-body"><canvas id="activityChart"></canvas></div></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('bookingsChart'), {
        type: 'line',
        data: { labels: @json($chart['labels']), datasets: [{ label: 'Bookings', data: @json($chart['bookings']), borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.15)', fill: true, tension: .35 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
    new Chart(document.getElementById('activityChart'), {
        type: 'doughnut',
        data: { labels: @json($byActivity->pluck('name')), datasets: [{ data: @json($byActivity->pluck('count')), backgroundColor: @json($byActivity->pluck('color')) }] },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
</script>
@endpush
