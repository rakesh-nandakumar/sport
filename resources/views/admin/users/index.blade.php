@extends('layouts.dashboard')
@section('title', 'Users')

@section('content')
<h1 class="display fs-1 mb-0">Users</h1>
<p class="text-muted">Change roles or remove accounts.</p>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Search name or email"></div>
    <div class="col-md-3"><select name="role" class="form-select form-select-sm"><option value="">All roles</option>@foreach($roles as $r)<option value="{{ $r->value }}" @selected(($filters['role'] ?? '') == $r->value)>{{ $r->label() }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-dark btn-sm w-100">Filter</button></div>
</form>

<div class="card shadow-sm border-0"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Venues</th><th>Bookings</th><th>Joined</th><th></th></tr></thead>
        <tbody>
        @foreach($users as $u)
            <tr>
                <td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->phone }}</td>
                <td><span class="badge bg-secondary">{{ $u->role()->label() }}</span></td>
                <td>{{ $u->venues_count }}</td><td>{{ $u->bookings_count }}</td><td class="small text-muted">{{ $u->created_at->format('d M Y') }}</td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-dark">Edit</a>
                    @if($u->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="d-inline" onsubmit="return confirm('Delete {{ $u->name }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div><div class="card-footer bg-white">{{ $users->links() }}</div></div>
@endsection
