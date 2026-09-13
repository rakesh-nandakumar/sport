@extends('layouts.app')
@section('title', 'Login · Sportee')

@section('content')
<section class="mx-auto max-w-md px-4 pb-16">
    <div class="rounded-3xl border border-gray-200 bg-white p-8 shadow-lg">
        <h1 class="display text-4xl text-gray-900">Welcome back</h1>
        <p class="text-sm text-gray-500">Log in to book, manage your venue, or check your alerts.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm">
                <span class="font-medium text-gray-700">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">
                @error('email')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
            </label>
            <label class="block text-sm">
                <span class="font-medium text-gray-700">Password</span>
                <input type="password" name="password" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">
                @error('password')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="remember" class="rounded"> Keep me logged in</label>
            <button class="btn-brand w-full py-3">Log in</button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">New here? <a href="{{ route('register') }}" class="font-semibold text-brand hover:underline">Create an account</a> · <a href="{{ route('register.vendor') }}" class="font-semibold text-brand hover:underline">List a venue</a></p>

        <div class="mt-6 rounded-xl bg-gray-50 p-4 text-xs text-gray-500">
            <p class="font-semibold text-gray-700">Demo accounts (password: <code>password</code>)</p>
            <p>Admin: admin@sportee.lk · Vendor: vendor@sportee.lk · Customer: customer@sportee.lk</p>
        </div>
    </div>
</section>
@endsection
