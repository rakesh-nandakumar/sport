@extends('layouts.dashboard')
@section('title', 'Games')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4">
    <div><h1 class="display fs-1 mb-0">Games</h1><p class="text-muted mb-0">Titles vendors can attach to gaming services (PlayStation, VR, arcade…).</p></div>
    <a href="{{ route('admin.games.create') }}" class="btn btn-danger"><i class="fa-solid fa-plus me-1"></i>Add game</a>
</div>

<div class="card shadow-sm border-0"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Game</th><th>Activity</th><th>Platform</th><th>Max players</th><th>Used by</th><th></th></tr></thead>
        <tbody>
        @forelse($games as $g)
            <tr>
                <td class="fw-semibold">{{ $g->name }}</td>
                <td><span class="badge" style="background: {{ $g->activityType->color }}">{{ $g->activityType->name }}</span></td>
                <td>{{ $g->platform }}</td><td>{{ $g->max_players ?: '—' }}</td><td>{{ $g->services_count }} services</td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('admin.games.edit', $g) }}" class="btn btn-sm btn-outline-dark">Edit</a>
                    <form method="POST" action="{{ route('admin.games.destroy', $g) }}" class="d-inline" onsubmit="return confirm('Delete {{ $g->name }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">No games yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
