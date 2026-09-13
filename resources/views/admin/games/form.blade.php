@extends('layouts.dashboard')
@section('title', $game->exists ? 'Edit '.$game->name : 'Add game')

@section('content')
<nav class="small text-muted mb-1"><a href="{{ route('admin.games.index') }}">Games</a> › {{ $game->exists ? $game->name : 'New' }}</nav>
<h1 class="display fs-1">{{ $game->exists ? $game->name : 'Add game' }}</h1>

<form method="POST" action="{{ $game->exists ? route('admin.games.update', $game) : route('admin.games.store') }}" class="card shadow-sm border-0" style="max-width:640px">
    @csrf @if($game->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-8"><label class="form-label">Name</label><input name="name" value="{{ old('name', $game->name) }}" class="form-control" required placeholder="EA FC 26"></div>
        <div class="col-md-4"><label class="form-label">Platform</label><input name="platform" value="{{ old('platform', $game->platform) }}" class="form-control" placeholder="PS5 / Xbox / PC / VR"></div>
        <div class="col-md-8"><label class="form-label">Activity type</label>
            <select name="activity_type_id" class="form-select" required>
                @foreach($activityTypes as $t)<option value="{{ $t->id }}" @selected(old('activity_type_id', $game->activity_type_id) == $t->id)>{{ $t->name }}</option>@endforeach
            </select>
            <div class="form-text">Only activity types with "customers pick a game" enabled are listed.</div>
        </div>
        <div class="col-md-4"><label class="form-label">Max players</label><input type="number" name="max_players" min="1" value="{{ old('max_players', $game->max_players) }}" class="form-control"></div>
    </div>
    <div class="card-footer bg-white d-flex gap-2"><button class="btn btn-danger">Save</button><a href="{{ route('admin.games.index') }}" class="btn btn-link text-muted">Cancel</a></div>
</form>
@endsection
