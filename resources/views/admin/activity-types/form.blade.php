@extends('layouts.dashboard')
@section('title', $type->exists ? 'Edit '.$type->name : 'Add activity type')

@section('content')
<nav class="small text-muted mb-1"><a href="{{ route('admin.activity-types.index') }}">Activity types</a> › {{ $type->exists ? $type->name : 'New' }}</nav>
<h1 class="display fs-1">{{ $type->exists ? $type->name : 'Add activity type' }}</h1>

<form method="POST" action="{{ $type->exists ? route('admin.activity-types.update', $type) : route('admin.activity-types.store') }}" enctype="multipart/form-data" class="card shadow-sm border-0" style="max-width:760px">
    @csrf @if($type->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">Name</label><input name="name" value="{{ old('name', $type->name) }}" class="form-control" required placeholder="e.g. Paintball"></div>
        <div class="col-md-6"><label class="form-label">Unit label <span class="text-muted small">— what a vendor's service is called</span></label><input name="unit_label" value="{{ old('unit_label', $type->unit_label) }}" class="form-control" required placeholder="Court / Station / Lane / Arena / Session"></div>
        <div class="col-md-6"><label class="form-label">Font Awesome icon class</label><input name="icon" value="{{ old('icon', $type->icon) }}" class="form-control" required placeholder="fa-solid fa-futbol"><div class="form-text">Preview: <i class="{{ old('icon', $type->icon) }}"></i> — browse at fontawesome.com/icons</div></div>
        <div class="col-md-3"><label class="form-label">Colour</label><input type="color" name="color" value="{{ old('color', $type->color) }}" class="form-control form-control-color w-100"></div>
        <div class="col-md-3"><label class="form-label">Default block (min)</label><input type="number" name="default_slot_minutes" value="{{ old('default_slot_minutes', $type->default_slot_minutes) }}" min="15" step="5" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="2" class="form-control">{{ old('description', $type->description) }}</textarea></div>
        <div class="col-12">
            <label class="form-label">Tile photo <span class="text-muted small">— shown as the background of the "Browse by activity" card</span></label>
            <div class="row g-3 align-items-start">
                <div class="col-md-4">
                    @if($type->imageUrl())
                        <img src="{{ $type->imageUrl() }}" alt="" class="rounded w-100" style="height:110px;object-fit:cover">
                        <div class="form-check mt-2"><input type="hidden" name="remove_image" value="0"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="rmimg"><label class="form-check-label small" for="rmimg">Remove photo</label></div>
                    @else
                        <div class="rounded bg-light d-grid text-muted small" style="height:110px;place-items:center">No photo yet</div>
                    @endif
                </div>
                <div class="col-md-8">
                    <input type="file" name="image_upload" accept="image/*" class="form-control mb-2">
                    <input name="image_url" value="{{ old('image_url') }}" class="form-control" placeholder="…or paste an image URL / path, e.g. /images/activities/futsal.jpg">
                    <div class="form-text">Landscape photos (≥ 1200×800) look best. Uploads are stored under storage/app/public/activity-types.</div>
                </div>
            </div>
        </div>
        <div class="col-md-4"><label class="form-label">Sort order</label><input type="number" name="sort_order" value="{{ old('sort_order', $type->sort_order ?? 0) }}" min="0" class="form-control" required></div>
        <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input type="hidden" name="requires_game" value="0"><input class="form-check-input" type="checkbox" name="requires_game" value="1" id="rg" @checked(old('requires_game', $type->requires_game))><label class="form-check-label" for="rg">Customers pick a game</label></div></div>
        <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input type="hidden" name="is_featured" value="0"><input class="form-check-input" type="checkbox" name="is_featured" value="1" id="ft" @checked(old('is_featured', $type->is_featured))><label class="form-check-label" for="ft">Featured on home page</label></div></div>
    </div>
    <div class="card-footer bg-white d-flex gap-2"><button class="btn btn-danger">Save</button><a href="{{ route('admin.activity-types.index') }}" class="btn btn-link text-muted">Cancel</a></div>
</form>
@endsection
