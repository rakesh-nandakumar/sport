@extends('layouts.dashboard')
@section('title', 'My venues')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4">
    <div><h1 class="display fs-1 mb-0">My venues</h1><p class="text-muted mb-0">Each venue can list any number of bookable services.</p></div>
    <a href="{{ route('vendor.venues.create') }}" class="btn btn-danger"><i class="fa-solid fa-plus me-1"></i>Add venue</a>
</div>

<div class="row g-4">
    @forelse($venues as $venue)
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <img src="{{ $venue->coverUrl() }}" class="card-img-top" style="height:160px;object-fit:cover" alt="">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title mb-0">{{ $venue->name }}</h5>
                        <span class="badge {{ $venue->is_approved ? 'bg-success' : 'bg-secondary' }}">{{ $venue->is_approved ? 'Live' : 'Hidden' }}</span>
                    </div>
                    <p class="text-muted small mb-2">{{ $venue->city }} · {{ $venue->phone }}</p>
                    <p class="small mb-0">{{ $venue->services_count }} services · {{ $venue->bookings_count }} bookings</p>
                </div>
                <div class="card-footer bg-white d-flex gap-2">
                    <a href="{{ route('vendor.venues.services.index', $venue) }}" class="btn btn-sm btn-dark flex-fill">Services</a>
                    <a href="{{ route('vendor.venues.edit', $venue) }}" class="btn btn-sm btn-outline-dark">Edit</a>
                    <a href="{{ route('venues.show', $venue) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">No venues yet. Add one to get started.</div></div>
    @endforelse
</div>
@endsection
