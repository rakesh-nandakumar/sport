@extends('layouts.app')
@section('title', 'Sign up · EntryPoint.lk')

@section('content')
<section class="mx-auto max-w-md px-4 pb-16">
    <div class="rounded-3xl border border-gray-200 bg-white p-8 shadow-lg">
        <img src="{{ asset('android-chrome-192x192.png') }}" alt="EntryPoint.lk" class="mx-auto mb-6 h-14 w-14 rounded-2xl">
        <h1 class="display text-4xl text-gray-900 text-center">Create your account</h1>
        <p class="text-sm text-gray-500 text-center">Book any sport or activity in seconds.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm">
                <span class="font-medium text-gray-700">Full name</span>
                <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">
                @error('name')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
            </label>
            <label class="block text-sm">
                <span class="font-medium text-gray-700">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">
                @error('email')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
            </label>
            <label class="block text-sm">
                <span class="font-medium text-gray-700">Mobile number</span>
                <input name="phone" value="{{ old('phone') }}" placeholder="07XXXXXXXX" inputmode="numeric" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">
                @error('phone')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">Password</span>
                    <input type="password" name="password" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">
                    @error('password')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">Confirm</span>
                    <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">
                </label>
            </div>
            <button class="btn-brand w-full py-3">Sign up</button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Already have an account? <a href="{{ route('login') }}" class="font-semibold text-brand hover:underline">Log in</a>
            <br>Own a venue? <a href="{{ route('register.vendor') }}" class="font-semibold text-brand hover:underline">Apply to list it on EntryPoint.lk</a>
        </p>
    </div>
</section>
@endsection
