@extends('layout')

@section('content')

<div class="form-shell">
    <div class="form-card">
        <div class="form-card__icon"><i class="fa-solid fa-user-plus"></i></div>
        <header>
            <h2>Create account</h2>
            <p>Sign up to book indoor facilities near you.</p>
        </header>

        <form method="POST" action="/customer">
            @csrf

            <div class="field">
                <label for="name" class="field-label">Name</label>
                <input type="text" id="name" class="field-input" name="name" value="{{ old('name') }}" />
                @error('name')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="email" class="field-label">Email</label>
                <input type="email" id="email" class="field-input" name="email" value="{{ old('email') }}" />
                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password" class="field-label">Password</label>
                <input type="password" id="password" class="field-input" name="password" />
                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password2" class="field-label">Confirm Password</label>
                <input type="password" id="password2" class="field-input" name="password_confirmation" />
                @error('password_confirmation')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign Up</button>

            <p class="form-footer">Already have an account? <a href="/login">Login</a></p>
        </form>
    </div>
</div>

@endsection
