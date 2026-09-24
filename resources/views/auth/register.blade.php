@extends('layouts.app')
@section('title', 'Sign up · EntryPoint.lk')

@section('content')
<section class="mx-auto max-w-md px-4 pb-16">
    <div class="rounded-3xl border border-gray-200 bg-white p-8 shadow-lg">
        <img src="{{ asset('android-chrome-192x192.png') }}" alt="EntryPoint.lk" class="mx-auto mb-6 h-14 w-14 rounded-2xl">
        <h1 class="display text-4xl text-gray-900 text-center">Create your account</h1>
        <p class="text-sm text-gray-500 text-center">Book any sport or activity in seconds.</p>

        <a href="{{ route('auth.google.redirect') }}" class="mt-6 flex w-full items-center justify-center gap-3 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">
            <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5"><path fill="#4285F4" d="M21.8 12.2c0-.7-.1-1.4-.2-2H12v3.8h5.5a4.7 4.7 0 0 1-2 3.1v2.5h3.2c1.9-1.8 3.1-4.4 3.1-7.4Z"/><path fill="#34A853" d="M12 22c2.7 0 5-.9 6.7-2.4l-3.2-2.5c-.9.6-2 .9-3.5.9-2.7 0-5-1.8-5.8-4.3H2.9v2.6A10 10 0 0 0 12 22Z"/><path fill="#FBBC05" d="M6.2 13.7a6 6 0 0 1 0-3.4V7.7H2.9a10 10 0 0 0 0 8.6l3.3-2.6Z"/><path fill="#EA4335" d="M12 6c1.5 0 2.9.5 4 1.6l3-3A10 10 0 0 0 2.9 7.7l3.3 2.6C7 7.8 9.3 6 12 6Z"/></svg>
            Continue with Google
        </a>
        @error('google')<p class="mt-2 text-center text-xs text-rose-600">{{ $message }}</p>@enderror

        <div class="my-6 flex items-center gap-3 text-xs font-medium uppercase tracking-wider text-gray-400"><span class="h-px flex-1 bg-gray-200"></span><span>or sign up with email</span><span class="h-px flex-1 bg-gray-200"></span></div>

        <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="space-y-4">
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
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex gap-3">
                    <i class="fa-solid fa-id-card mt-1 text-amber-700"></i>
                    <div>
                        <p class="font-semibold text-amber-950">Add your NIC now <span class="font-normal">(optional)</span></p>
                        <p class="mt-1 text-xs leading-5 text-amber-900">Upload both sides now to use Pay at Venue without being asked again at checkout. You can skip this step, but Pay at Venue will stay unavailable until both images are on your account.</p>
                    </div>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="mb-2 text-sm font-medium text-gray-700">NIC front</p>
                        <x-file-drop name="nic_front" accept=".jpg,.jpeg,.png,image/*" :max-size="5" :camera="true" hint="Optional · JPG or PNG · up to 5 MB" />
                        @error('nic_front')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <p class="mb-2 text-sm font-medium text-gray-700">NIC back</p>
                        <x-file-drop name="nic_back" accept=".jpg,.jpeg,.png,image/*" :max-size="5" :camera="true" hint="Optional · JPG or PNG · up to 5 MB" />
                        @error('nic_back')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                    </div>
                </div>
                <p class="mt-3 text-xs text-amber-900"><i class="fa-solid fa-shield-halved mr-1"></i>These images are stored privately on your account and are only shared with the venue and administrators for Pay at Venue bookings.</p>
            </div>
            <p class="rounded-xl bg-sky-50 p-3 text-xs text-sky-900"><i class="fa-solid fa-envelope-circle-check mr-1"></i>After signing up, check your email and use the verification link. You must verify your email before booking or accessing a vendor workspace.</p>
            <button class="btn-brand w-full py-3">Sign up</button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Already have an account? <a href="{{ route('login') }}" class="font-semibold text-brand hover:underline">Log in</a>
            <br>Own a venue? <a href="{{ route('register.vendor') }}" class="font-semibold text-brand hover:underline">Apply to list it on EntryPoint.lk</a>
        </p>
    </div>
</section>
@endsection
