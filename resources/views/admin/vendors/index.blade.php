@extends('layouts.dashboard')
@section('title', 'Vendors')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="display fs-1 mb-0">Vendors</h1>
        <p class="text-muted mb-0">The businesses that list venues. Nothing a vendor lists is visible to customers until you activate them.</p>
    </div>
    <div class="d-flex gap-2">
        @foreach($statuses as $s)
            <a href="{{ route('admin.vendors.index', ['status' => $s->value]) }}" class="btn btn-sm {{ ($filters['status'] ?? '') === $s->value ? 'btn-dark' : 'btn-outline-dark' }}">{{ $s->label() }} <span class="badge {{ $s->bsBadge() }} ms-1">{{ $counts[$s->value] ?? 0 }}</span></a>
        @endforeach
        @if(! empty($filters['status']))<a href="{{ route('admin.vendors.index') }}" class="btn btn-sm btn-link text-muted">All</a>@endif
    </div>
</div>

<form method="GET" class="row g-2 mb-3">
    <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
    <div class="col-md-5"><input name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Search business, owner, email, city or BR number"></div>
    <div class="col-md-2"><button class="btn btn-dark btn-sm w-100">Search</button></div>
</form>

<div class="card shadow-sm border-0"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Business</th><th>Owner / login</th><th>Location</th><th>Type · BR no.</th><th>Venues</th><th>Bookings</th><th>Applied</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($vendors as $v)
            <tr>
                <td><a href="{{ route('admin.vendors.show', $v) }}" class="fw-semibold text-decoration-none">{{ $v->business_name }}</a>@if($v->hasDocuments())<i class="fa-solid fa-paperclip text-muted ms-1 small" title="Documents attached"></i>@endif</td>
                <td>{{ $v->user->name }}<div class="small text-muted">{{ $v->user->email }} · {{ $v->contact_phone }}</div></td>
                <td>{{ $v->city }}<div class="small text-muted">{{ $v->district }}</div></td>
                <td class="small">{{ $v->businessTypeLabel() }}<div class="text-muted">{{ $v->registration_number ?: '—' }}</div></td>
                <td>{{ $v->user->venues_count }}</td><td>{{ $v->user->bookings_count }}</td>
                <td class="small text-muted">{{ $v->created_at->format('d M Y') }}</td>
                <td><span class="badge {{ $v->status->bsBadge() }}">{{ $v->status->label() }}</span></td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('admin.vendors.show', $v) }}" class="btn btn-sm btn-outline-dark">Review</a>
                    @if(auth()->user()->isAdmin() && $v->status === \App\Enums\VendorStatus::Pending)
                        <form method="POST" action="{{ route('admin.vendors.status', $v) }}" class="d-inline">@csrf<input type="hidden" name="status" value="active"><button class="btn btn-sm btn-success">Activate</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center text-muted py-5">No vendors match.</td></tr>
        @endforelse
        </tbody>
    </table>
</div><div class="card-footer bg-white">{{ $vendors->links() }}</div></div>
@endsection
