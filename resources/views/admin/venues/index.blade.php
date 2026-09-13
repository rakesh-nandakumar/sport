@extends('layouts.dashboard')
@section('title', 'Venues')

@section('content')
<h1 class="display fs-1 mb-0">Venues</h1>
<p class="text-muted">Approve, feature, or remove venues across the platform.</p>

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
                <td>{{ $v->owner->name }}<div class="small text-muted">{{ $v->owner->email }}</div></td>
                <td>{{ $v->city }}</td><td>{{ $v->services_count }}</td><td>{{ $v->bookings_count }}</td>
                <td><span class="badge {{ $v->is_approved ? 'bg-success' : 'bg-secondary' }}">{{ $v->is_approved ? 'Live' : 'Hidden' }}</span></td>
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
