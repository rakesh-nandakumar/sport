@extends('layouts.app')
@section('title', 'List your venue · EntryPoint.lk')

@section('content')
<section class="mx-auto max-w-4xl px-4 pb-16">
    <div class="text-center">
        <img src="{{ asset('android-chrome-192x192.png') }}" alt="EntryPoint.lk" class="mx-auto mb-4 h-14 w-14 rounded-2xl">
        <h1 class="display text-4xl md:text-5xl text-gray-900">Apply to list your venue</h1>
        <p class="mx-auto mt-2 max-w-2xl text-sm text-gray-500">Tell us about your business so we can verify it. Applications are reviewed by our team (usually within 1–2 working days). You can set up your venues and services while you wait — they go live the moment you're approved. Listing is free.</p>
    </div>

    @if($errors->any())
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-semibold">Please fix the highlighted fields:</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.vendor') }}" enctype="multipart/form-data" class="mt-8 space-y-6" x-data="{ type: @js(old('business_type', 'private_limited')), locating: false, lat: @js(old('latitude')), lng: @js(old('longitude')) }">
        @csrf

        {{-- 1. Account --}}
        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm md:p-8">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900"><span class="grid h-7 w-7 place-items-center rounded-full bg-gray-900 text-xs text-white">1</span>Your login</h2>
            <p class="text-sm text-gray-500">The person who will manage bookings day to day.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm"><span class="font-medium text-gray-700">Your full name</span><input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">@error('name')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Login email</span><input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">@error('email')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Your mobile</span><input name="phone" value="{{ old('phone') }}" placeholder="07XXXXXXXX" inputmode="numeric" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">@error('phone')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm"><span class="font-medium text-gray-700">Password</span><input type="password" name="password" required minlength="8" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">@error('password')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block text-sm"><span class="font-medium text-gray-700">Confirm</span><input type="password" name="password_confirmation" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5"></label>
                </div>
            </div>
        </div>

        {{-- 2. Business --}}
        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm md:p-8">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900"><span class="grid h-7 w-7 place-items-center rounded-full bg-gray-900 text-xs text-white">2</span>The business</h2>
            <p class="text-sm text-gray-500">Legal details we use to verify you. Customers only see the trading name.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm sm:col-span-2"><span class="font-medium text-gray-700">Business / trading name</span><input name="business_name" value="{{ old('business_name') }}" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5" placeholder="e.g. CR7 Futsal Arena (Pvt) Ltd">@error('business_name')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Business type</span>
                    <select name="business_type" x-model="type" required class="mt-1 w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5">
                        @foreach($businessTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                    @error('business_type')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Business registration no. <span class="text-gray-400" x-text="['private_limited','partnership'].includes(type) ? '(required)' : '(if registered)'"></span></span><input name="registration_number" value="{{ old('registration_number') }}" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5" placeholder="PV 123456 / WP/12345">@error('registration_number')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Owner / director NIC</span><input name="owner_nic" value="{{ old('owner_nic') }}" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5" placeholder="199012345678 or 901234567V">@error('owner_nic')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Years in operation</span><input type="number" name="years_operating" value="{{ old('years_operating') }}" min="0" max="100" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5"></label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Contact person</span><input name="contact_person" value="{{ old('contact_person', old('name')) }}" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">@error('contact_person')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Business email</span><input type="email" name="business_email" value="{{ old('business_email') }}" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5" placeholder="bookings@yourvenue.lk">@error('business_email')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Business phone</span><input name="contact_phone" value="{{ old('contact_phone') }}" required inputmode="numeric" placeholder="0112345678" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">@error('contact_phone')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Alternate phone <span class="text-gray-400">(optional)</span></span><input name="alt_phone" value="{{ old('alt_phone') }}" inputmode="numeric" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">@error('alt_phone')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Website <span class="text-gray-400">(optional)</span></span><input type="url" name="website" value="{{ old('website') }}" placeholder="https://" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">@error('website')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Facebook page <span class="text-gray-400">(optional)</span></span><input name="facebook" value="{{ old('facebook') }}" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5"></label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Instagram <span class="text-gray-400">(optional)</span></span><input name="instagram" value="{{ old('instagram') }}" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5"></label>
                <label class="block text-sm"><span class="font-medium text-gray-700">How many venues will you list?</span><input type="number" name="venue_count_estimate" value="{{ old('venue_count_estimate', 1) }}" min="1" max="100" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5"></label>
                <label class="block text-sm sm:col-span-2"><span class="font-medium text-gray-700">About the business</span><textarea name="description" rows="3" required minlength="30" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5" placeholder="What you offer, how long you've been running, anything that helps us verify you.">{{ old('description') }}</textarea>@error('description')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <div class="sm:col-span-2">
                    <span class="text-sm font-medium text-gray-700">What will you list?</span>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($activityTypes as $type)
                            <label class="chip inline-flex cursor-pointer items-center gap-2 px-3 py-1.5 text-sm has-[:checked]:border-brand has-[:checked]:bg-brand-soft">
                                <input type="checkbox" name="activity_type_ids[]" value="{{ $type->id }}" class="rounded" @checked(in_array($type->id, old('activity_type_ids', [])))>
                                <i class="{{ $type->icon }}" style="color: {{ $type->color }}"></i>{{ $type->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Location --}}
        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm md:p-8">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900"><span class="grid h-7 w-7 place-items-center rounded-full bg-gray-900 text-xs text-white">3</span>Where you are</h2>
            <p class="text-sm text-gray-500">Registered business address. Your venue addresses can differ — you add those next.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm sm:col-span-2"><span class="font-medium text-gray-700">Address line 1</span><input name="address_line1" value="{{ old('address_line1') }}" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5" placeholder="No. 48, Havelock Road">@error('address_line1')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block text-sm sm:col-span-2"><span class="font-medium text-gray-700">Address line 2 <span class="text-gray-400">(optional)</span></span><input name="address_line2" value="{{ old('address_line2') }}" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5" placeholder="Havelock Town"></label>
                <label class="block text-sm"><span class="font-medium text-gray-700">City / town</span><input name="city" value="{{ old('city') }}" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5" placeholder="Colombo 05">@error('city')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block text-sm"><span class="font-medium text-gray-700">District</span>
                    <select name="district" required class="mt-1 w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5">
                        <option value="">Select…</option>
                        @foreach($districts as $d)<option value="{{ $d }}" @selected(old('district') === $d)>{{ $d }}</option>@endforeach
                    </select>
                    @error('district')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Postal code <span class="text-gray-400">(optional)</span></span><input name="postal_code" value="{{ old('postal_code') }}" inputmode="numeric" maxlength="5" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5" placeholder="00500">@error('postal_code')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <div class="block text-sm">
                    <span class="font-medium text-gray-700">Map pin <span class="text-gray-400">(optional, helps "near you" search)</span></span>
                    <div class="mt-1 flex gap-2">
                        <input name="latitude" x-model="lat" placeholder="Latitude" class="w-full rounded-xl border border-gray-200 px-3 py-2.5">
                        <input name="longitude" x-model="lng" placeholder="Longitude" class="w-full rounded-xl border border-gray-200 px-3 py-2.5">
                        <button type="button" class="btn-ghost !px-3 whitespace-nowrap" :disabled="locating" @click="locating = true; navigator.geolocation.getCurrentPosition(p => { lat = p.coords.latitude.toFixed(6); lng = p.coords.longitude.toFixed(6); locating = false }, () => locating = false)"><i class="fa-solid" :class="locating ? 'fa-spinner fa-spin' : 'fa-location-crosshairs'"></i></button>
                    </div>
                    <span class="text-xs text-gray-400">Tap the icon while at the venue, or paste coordinates from Google Maps.</span>
                    @error('latitude')<span class="block text-xs text-rose-600">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        {{-- 4. Documents --}}
        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm md:p-8">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900"><span class="grid h-7 w-7 place-items-center rounded-full bg-gray-900 text-xs text-white">4</span>Verification documents</h2>
            <p class="text-sm text-gray-500">Seen only by our review team. JPG, PNG or PDF, up to 8 MB each.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm"><span class="font-medium text-gray-700">Business registration certificate <span class="text-gray-400" x-text="['private_limited','partnership'].includes(type) ? '(required)' : '(if you have one)'"></span></span><input type="file" name="br_document" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm">@error('br_document')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block text-sm"><span class="font-medium text-gray-700">Owner NIC copy (front)</span><input type="file" name="nic_document" accept=".jpg,.jpeg,.png,.pdf" required class="mt-1 block w-full text-sm">@error('nic_document')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
            </div>
            <label class="mt-6 flex items-start gap-3 text-sm text-gray-600">
                <input type="checkbox" name="terms" value="1" required class="mt-1 rounded" @checked(old('terms'))>
                <span>I confirm the information above is accurate, that I'm authorised to list this business, and I accept the EntryPoint.lk vendor terms (honouring confirmed bookings, verifying bank transfers promptly, and keeping opening hours and prices up to date).</span>
            </label>
            @error('terms')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
        </div>

        <button class="btn-brand w-full py-4 text-base">Submit application</button>
        <p class="text-center text-sm text-gray-500">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-brand hover:underline">Log in</a> · Just want to book? <a href="{{ route('register') }}" class="font-semibold text-brand hover:underline">Customer sign up</a></p>
    </form>
</section>
@endsection
