@extends('layout')

@section('content')

<div class="form-shell">
    <div class="form-card">
        <div class="form-card__icon"><i class="fa-solid fa-right-to-bracket"></i></div>
        <header>
            <h2>Welcome back</h2>
            <p>Login to book your next game or manage your facility.</p>
        </header>

        <form method="POST" action="/users/authenticate">
            @csrf

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

            <button type="submit" class="btn btn-primary btn-block">Sign in</button>

            <p class="form-footer">Don't have an account? <a href="/register">Sign up</a></p>
        </form>
    </div>
</div>

@endsection
