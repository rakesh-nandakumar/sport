@extends('layouts.dashboard')
@section('title', 'Venues')

@section('content')
<h1 class="display fs-1 mb-0">Venues</h1>
<p class="text-muted">Approve, feature, or remove venues. A venue is only live when it is approved <em>and</em> its vendor is active (see <a href="{{ route('admin.vendors.index') }}">Vendors</a>).</p>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Search venues"></div>
    <div class="col-md-3"><select name="status" class="form-select form-select-sm"><option value="">All</option><option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Hidden / pending</option></select></div>
    <div class="col-md-2"><button class="btn btn-dark btn-sm w-100">Filter</button></div>
</form>

<div class="card shadow-sm border-0"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Venue</th><th>Owner</th><th>City</th><th>Services</th><th>Bookings</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($venues as $v)
            <tr>
                <td><a href="{{ route('venues.show', $v) }}" target="_blank" class="fw-semibold text-decoration-none">{{ $v->name }}</a>@if($v->is_featured)<span class="badge bg-danger ms-1">Featured</span>@endif</td>
                <td>{{ $v->owner->name }}<div class="small text-muted">{{ $v->owner->email }}</div>@if($v->owner->vendorProfile)<a href="{{ route('admin.vendors.show', $v->owner->vendorProfile) }}" class="badge {{ $v->owner->vendorStatus()->bsBadge() }} text-decoration-none">{{ $v->owner->vendorStatus()->label() }}</a>@endif</td>
                <td>{{ $v->city }}</td><td>{{ $v->services_count }}</td><td>{{ $v->bookings_count }}</td>
                <td>@if($v->isLive())<span class="badge bg-success">Live</span>@elseif($v->is_approved)<span class="badge bg-warning text-dark" title="Approved, but the vendor is not active">Vendor inactive</span>@else<span class="badge bg-secondary">Hidden</span>@endif</td>
                <td class="text-end text-nowrap">
                    <form method="POST" action="{{ route('admin.venues.approval', $v) }}" class="d-inline">@csrf<button class="btn btn-sm {{ $v->is_approved ? 'btn-outline-secondary' : 'btn-success' }}">{{ $v->is_approved ? 'Hide' : 'Approve' }}</button></form>
                    <form method="POST" action="{{ route('admin.venues.featured', $v) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-dark">{{ $v->is_featured ? 'Unfeature' : 'Feature' }}</button></form>
                    <a href="{{ route('vendor.venues.edit', $v) }}" class="btn btn-sm btn-outline-dark">Edit</a>
                    <form method="POST" action="{{ route('admin.venues.destroy', $v) }}" class="d-inline" onsubmit="return confirm('Delete {{ $v->name }} and all its data?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-5">No venues.</td></tr>
        @endforelse
        </tbody>
    </table>
</div><div class="card-footer bg-white">{{ $venues->links() }}</div></div>
@endsection
