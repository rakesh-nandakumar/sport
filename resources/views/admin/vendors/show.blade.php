@extends('layouts.dashboard')
@section('title', $vendor->business_name)

@section('content')
<nav class="small text-muted mb-1"><a href="{{ route('admin.vendors.index') }}">Vendors</a> › {{ $vendor->business_name }}</nav>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="display fs-1 mb-1">{{ $vendor->business_name }}</h1>
        <span class="badge {{ $vendor->status->bsBadge() }}">{{ $vendor->status->label() }}</span>
        <span class="badge bg-light text-dark">{{ $vendor->businessTypeLabel() }}</span>
        <span class="text-muted small ms-2">Applied {{ $vendor->created_at->format('d M Y, h:i A') }}</span>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-semibold">Business details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Registration no.</dt><dd class="col-sm-8">{{ $vendor->registration_number ?: '— (not registered)' }}</dd>
                    <dt class="col-sm-4">Owner NIC</dt><dd class="col-sm-8 font-monospace">{{ $vendor->owner_nic }}</dd>
                    <dt class="col-sm-4">Contact person</dt><dd class="col-sm-8">{{ $vendor->contact_person }}</dd>
                    <dt class="col-sm-4">Phones</dt><dd class="col-sm-8"><a href="tel:{{ $vendor->contact_phone }}">{{ $vendor->contact_phone }}</a>@if($vendor->alt_phone) · <a href="tel:{{ $vendor->alt_phone }}">{{ $vendor->alt_phone }}</a>@endif</dd>
                    <dt class="col-sm-4">Business email</dt><dd class="col-sm-8"><a href="mailto:{{ $vendor->business_email }}">{{ $vendor->business_email }}</a></dd>
                    <dt class="col-sm-4">Online</dt><dd class="col-sm-8">
                        @if($vendor->website)<a href="{{ $vendor->website }}" target="_blank" rel="noopener">{{ $vendor->website }}</a><br>@endif
                        @if($vendor->facebook)<span class="small"><i class="fa-brands fa-facebook me-1"></i>{{ $vendor->facebook }}</span><br>@endif
                        @if($vendor->instagram)<span class="small"><i class="fa-brands fa-instagram me-1"></i>{{ $vendor->instagram }}</span>@endif
                        @if(! $vendor->website && ! $vendor->facebook && ! $vendor->instagram)—@endif
                    </dd>
                    <dt class="col-sm-4">Address</dt><dd class="col-sm-8">{{ $vendor->fullAddress() }}
                        @if($vendor->latitude)<br><a href="https://www.google.com/maps/search/?api=1&query={{ $vendor->latitude }},{{ $vendor->longitude }}" target="_blank" rel="noopener" class="small"><i class="fa-solid fa-map-location-dot me-1"></i>{{ $vendor->latitude }}, {{ $vendor->longitude }} — open in Maps</a>@endif
                    </dd>
                    <dt class="col-sm-4">Years operating</dt><dd class="col-sm-8">{{ $vendor->years_operating ?? '—' }}</dd>
                    <dt class="col-sm-4">Plans to list</dt><dd class="col-sm-8">{{ $vendor->venue_count_estimate ?? '—' }} venue(s) · {{ $activityTypes->pluck('name')->join(', ') ?: 'activities not specified' }}</dd>
                    <dt class="col-sm-4">About</dt><dd class="col-sm-8" style="white-space:pre-line">{{ $vendor->description ?: '—' }}</dd>
                    <dt class="col-sm-4">Terms accepted</dt><dd class="col-sm-8">{{ $vendor->terms_accepted_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-semibold">Login account</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $vendor->user->name }}</dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $vendor->user->email }}</dd>
                    <dt class="col-sm-4">Mobile</dt><dd class="col-sm-8">{{ $vendor->user->phone }}</dd>
                    <dt class="col-sm-4">Joined</dt><dd class="col-sm-8">{{ $vendor->user->created_at->format('d M Y') }} · <a href="{{ route('admin.users.edit', $vendor->user) }}">edit user</a></dd>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">Venues ({{ $vendor->user->venues->count() }})</div>
            <div class="list-group list-group-flush">
                @forelse($vendor->user->venues as $venue)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div><a href="{{ route('venues.show', $venue) }}" target="_blank" class="fw-semibold text-decoration-none">{{ $venue->name }}</a><div class="small text-muted">{{ $venue->city }} · {{ $venue->services_count }} services · {{ $venue->bookings_count }} bookings</div></div>
                        <span class="badge {{ $venue->is_approved ? 'bg-success' : 'bg-secondary' }}">{{ $venue->is_approved ? 'Approved' : 'Hidden' }}</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No venues yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-semibold">Documents</div>
            <div class="card-body d-grid gap-2">
                @if($vendor->br_document_path)
                    <a href="{{ route('admin.vendors.document', [$vendor, 'br']) }}" target="_blank" class="btn btn-outline-primary"><i class="fa-solid fa-file-lines me-1"></i>Business registration</a>
                @else
                    <span class="text-muted small">No business registration certificate uploaded.</span>
                @endif
                @if($vendor->nic_document_path)
                    <a href="{{ route('admin.vendors.document', [$vendor, 'nic']) }}" target="_blank" class="btn btn-outline-primary"><i class="fa-solid fa-id-card me-1"></i>Owner NIC</a>
                @else
                    <span class="text-muted small">No NIC copy uploaded.</span>
                @endif
                <div class="form-text">Stored privately; these links only work for staff.</div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">Moderation</div>
            <div class="card-body">
                @if($vendor->reviewed_at)
                    <p class="small text-muted">Last reviewed by {{ $vendor->reviewer?->name ?? 'staff' }} on {{ $vendor->reviewed_at->format('d M Y, h:i A') }}.</p>
                    @if($vendor->review_notes)<p class="small border-start border-3 ps-2">{{ $vendor->review_notes }}</p>@endif
                @endif
                @if(auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('admin.vendors.status', $vendor) }}">
                        @csrf
                        <label class="form-label small fw-semibold">Set status</label>
                        <select name="status" class="form-select mb-2">
                            @foreach($statuses as $s)<option value="{{ $s->value }}" @selected(old('status', $vendor->status->value) === $s->value)>{{ $s->label() }}</option>@endforeach
                        </select>
                        <textarea name="review_notes" rows="3" class="form-control mb-2" placeholder="Note to the vendor (required when suspending or rejecting)">{{ old('review_notes') }}</textarea>
                        <button class="btn btn-danger w-100">Save status</button>
                        <div class="form-text mt-2">
                            <strong>Active</strong> — venues go live.<br>
                            <strong>Suspended</strong> — venues hidden, bookings paused, vendor keeps login.<br>
                            <strong>Rejected</strong> — application declined.
                        </div>
                    </form>
                @else
                    <p class="text-muted small mb-0">Only a Super Administrator can change a vendor's status.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
