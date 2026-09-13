@extends('layouts.dashboard')
@section('title', 'Edit '.$user->name)

@section('content')
<nav class="small text-muted mb-1"><a href="{{ route('admin.users.index') }}">Users</a> › {{ $user->name }}</nav>
<h1 class="display fs-1">Edit user</h1>

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="card shadow-sm border-0" style="max-width:640px">
    @csrf @method('PUT')
    <div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">Name</label><input name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone', $user->phone) }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Role</label><select name="role_id" class="form-select">@foreach($roles as $r)<option value="{{ $r->value }}" @selected(old('role_id', $user->role_id->value) == $r->value)>{{ $r->label() }}</option>@endforeach</select></div>
    </div>
    <div class="card-footer bg-white d-flex gap-2"><button class="btn btn-danger">Save</button><a href="{{ route('admin.users.index') }}" class="btn btn-link text-muted">Cancel</a></div>
</form>
@endsection
