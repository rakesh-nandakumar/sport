@extends('layouts.dashboard')
@section('title', 'Bookings')

@section('content')
<h1 class="display fs-1 mb-0">Bookings</h1>
<p class="text-muted">Confirm holds, verify transfers, and manage cancellations.</p>

<form method="GET" class="card shadow-sm border-0 mb-4"><div class="card-body row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label small">Venue</label><select name="venue" class="form-select form-select-sm"><option value="">All venues</option>@foreach($venues as $v)<option value="{{ $v->id }}" @selected(($filters['venue'] ?? '') == $v->id)>{{ $v->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="">Any status</option>@foreach($statuses as $s)<option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label small">Date</label><input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><div class="form-check"><input type="checkbox" name="pending_payment" value="1" class="form-check-input" id="pp" @checked($filters['pending_payment'] ?? false)><label for="pp" class="form-check-label small">Awaiting verification</label></div></div>
    <div class="col-md-1"><button class="btn btn-dark btn-sm w-100">Filter</button></div>
</div></form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Ref</th><th>When</th><th>Service</th><th>Customer</th><th>Payment</th><th>Status</th><th class="text-end">Total</th><th></th></tr></thead>
            <tbody>
            @forelse($bookings as $b)
                <tr>
                    <td class="font-monospace small">{{ $b->reference }}</td>
                    <td><div>{{ $b->starts_at->format('D d M') }}</div><div class="small text-muted">{{ $b->starts_at->format('h:i A') }} – {{ $b->ends_at->format('h:i A') }}</div></td>
                    <td><div>{{ $b->service->name }}</div><div class="small text-muted">{{ $b->venue->name }} · {{ $b->option->name }}</div></td>
                    <td><div>{{ $b->customer_name }}</div><div class="small text-muted">{{ $b->customer_phone }}</div></td>
                    <td><div class="small">{{ $b->payment_method->label() }}</div><span class="badge {{ $b->payment_status->bsBadge() }}">{{ $b->payment_status->label() }}</span></td>
                    <td><span class="badge {{ $b->status->bsBadge() }}">{{ $b->status->label() }}</span>@if($b->isLocked())<i class="fa-solid fa-lock ms-1 text-success small" title="Locked"></i>@endif</td>
                    <td class="text-end fw-semibold">{{ lkr($b->total) }}</td>
                    <td class="text-end"><a href="{{ route('vendor.bookings.show', $b) }}" class="btn btn-sm btn-outline-dark">Manage</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-5">No bookings match.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $bookings->links() }}</div>
</div>
@endsection
