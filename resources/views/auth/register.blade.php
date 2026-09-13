@extends('layouts.app')
@section('title', ($asVendor ? 'List your venue' : 'Sign up').' · Sportee')

@section('content')
<section class="mx-auto max-w-md px-4 pb-16">
    <div class="rounded-3xl border border-gray-200 bg-white p-8 shadow-lg">
        @if($asVendor)
            <h1 class="display text-4xl text-gray-900">List your venue</h1>
            <p class="text-sm text-gray-500">Create a vendor account, add your venue and services, and start taking bookings today. It's free.</p>
        @else
            <h1 class="display text-4xl text-gray-900">Create your account</h1>
            <p class="text-sm text-gray-500">Book any sport or activity in seconds.</p>
        @endif

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="account_type" value="{{ $asVendor ? 'vendor' : 'customer' }}">
            <label class="block text-sm">
                <span class="font-medium text-gray-700">{{ $asVendor ? 'Owner / manager name' : 'Full name' }}</span>
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
            <button class="btn-brand w-full py-3">{{ $asVendor ? 'Create vendor account' : 'Sign up' }}</button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Already have an account? <a href="{{ route('login') }}" class="font-semibold text-brand hover:underline">Log in</a>
            @if($asVendor)
                <br>Just want to book? <a href="{{ route('register') }}" class="font-semibold text-brand hover:underline">Customer sign up</a>
            @else
                <br>Own a venue? <a href="{{ route('register.vendor') }}" class="font-semibold text-brand hover:underline">List it on Sportee</a>
            @endif
        </p>
    </div>
</section>
@endsection
