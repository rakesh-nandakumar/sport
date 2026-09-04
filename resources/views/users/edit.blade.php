@extends('layout')

@section('content')

<div class="form-shell">
    <div class="form-card">
        <div class="form-card__icon"><i class="fa-solid fa-user-pen"></i></div>
        <header>
            <h2>Edit User</h2>
            <p>Update account details and permissions.</p>
        </header>

        <form method="POST" action="/admin/users/update/{{ $users->id }}">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="name" class="field-label">Name</label>
                <input type="text" id="name" class="field-input" name="name" value="{{ $users->name }}" />
                @error('name')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="email" class="field-label">Email</label>
                <input type="email" id="email" class="field-input" name="email" value="{{ $users->email }}" />
                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="role_id" class="field-label">Role</label>
                <select id="role_id" name="role_id" class="field-input">
                    <option value="">Select Role</option>
                    @foreach (\App\Enums\Role::cases() as $role)
                        <option value="{{ $role->value }}"
                            {{ old('role_id', $users->role_id->value) == $role->value ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', \Illuminate\Support\Str::snake($role->name))) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/" class="text-sm font-medium text-gray-500 hover:text-gray-800">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
