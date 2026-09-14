@extends('layouts.dashboard')
@section('title', 'Activity types')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4">
    <div><h1 class="display fs-1 mb-0">Activity types</h1><p class="text-muted mb-0">The catalogue vendors pick from. Add anything — paintball, bowling, yoga, go-karting.</p></div>
    <a href="{{ route('admin.activity-types.create') }}" class="btn btn-danger"><i class="fa-solid fa-plus me-1"></i>Add type</a>
</div>

<div class="card shadow-sm border-0"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>#</th><th>Type</th><th>Unit label</th><th>Default block</th><th>Game selection</th><th>Games</th><th>Services</th><th>Featured</th><th></th></tr></thead>
        <tbody>
        @foreach($types as $t)
            <tr>
                <td class="text-muted">{{ $t->sort_order }}</td>
                <td class="d-flex align-items-center gap-2">
                    @if($t->imageUrl())<img src="{{ $t->imageUrl() }}" alt="" class="rounded" style="width:56px;height:40px;object-fit:cover">@else<span class="badge" style="background: {{ $t->color }};width:56px;height:40px;display:grid;place-items:center;font-size:1rem"><i class="{{ $t->icon }}"></i></span>@endif
                    <div><strong>{{ $t->name }}</strong><div class="small text-muted">{{ $t->slug }}</div></div>
                </td>
                <td>{{ $t->unit_label }}</td><td>{{ $t->default_slot_minutes }} min</td>
                <td>{{ $t->requires_game ? 'Yes' : '—' }}</td><td>{{ $t->games_count }}</td><td>{{ $t->services_count }}</td>
                <td>{{ $t->is_featured ? '★' : '—' }}</td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('admin.activity-types.edit', $t) }}" class="btn btn-sm btn-outline-dark">Edit</a>
                    <form method="POST" action="{{ route('admin.activity-types.destroy', $t) }}" class="d-inline" onsubmit="return confirm('Delete {{ $t->name }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection
