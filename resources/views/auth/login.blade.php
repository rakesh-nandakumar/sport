@extends('layouts.app')
@section('title', 'Login · EntryPoint.lk')

@section('content')
<section class="mx-auto max-w-md px-4 pb-16">
    <div class="rounded-3xl border border-gray-200 bg-white p-8 shadow-lg">
        <img src="{{ asset('android-chrome-192x192.png') }}" alt="EntryPoint.lk" class="mx-auto mb-6 h-14 w-14 rounded-2xl">
        <h1 class="display text-4xl text-gray-900 text-center">Welcome back</h1>
        <p class="text-sm text-gray-500 text-center">Log in to book, manage your venue, or check your notifications.</p>

        <a href="{{ route('auth.google.redirect') }}" class="mt-6 flex w-full items-center justify-center gap-3 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">
            <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5"><path fill="#4285F4" d="M21.8 12.2c0-.7-.1-1.4-.2-2H12v3.8h5.5a4.7 4.7 0 0 1-2 3.1v2.5h3.2c1.9-1.8 3.1-4.4 3.1-7.4Z"/><path fill="#34A853" d="M12 22c2.7 0 5-.9 6.7-2.4l-3.2-2.5c-.9.6-2 .9-3.5.9-2.7 0-5-1.8-5.8-4.3H2.9v2.6A10 10 0 0 0 12 22Z"/><path fill="#FBBC05" d="M6.2 13.7a6 6 0 0 1 0-3.4V7.7H2.9a10 10 0 0 0 0 8.6l3.3-2.6Z"/><path fill="#EA4335" d="M12 6c1.5 0 2.9.5 4 1.6l3-3A10 10 0 0 0 2.9 7.7l3.3 2.6C7 7.8 9.3 6 12 6Z"/></svg>
            Continue with Google
        </a>
        @error('google')<p class="mt-2 text-center text-xs text-rose-600">{{ $message }}</p>@enderror

        <div class="my-6 flex items-center gap-3 text-xs font-medium uppercase tracking-wider text-gray-400"><span class="h-px flex-1 bg-gray-200"></span><span>or log in with email</span><span class="h-px flex-1 bg-gray-200"></span></div>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
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

        {{-- <div class="mt-6 rounded-xl bg-gray-50 p-4 text-xs text-gray-500">
            <p class="font-semibold text-gray-700">Demo accounts (password: <code>password</code>)</p>
            <p>Super Administrator: admin@entrypoint.lk · Vendor: vendor@entrypoint.lk · Customer: customer@entrypoint.lk</p>
        </div> --}}
    </div>
</section>
@endsection
