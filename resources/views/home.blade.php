@extends('layouts.app')
@section('main-class', '')

@section('content')
{{-- Hero --}}
<section class="hero !h-auto min-h-[640px] flex items-center">
    <div class="hero-content !w-full !p-0"><div class="mx-auto max-w-6xl px-4 pt-32 pb-16">
        <p class="mb-3 text-sm font-semibold uppercase tracking-[.3em] text-red-400">Sri Lanka's sports & activity marketplace</p>
        <h1 class="!mb-4">Book any sport.<br class="hidden sm:block"> Any time. Any place.</h1>
        <p class="!mb-8 max-w-2xl !tracking-normal !text-base md:!text-lg text-gray-200">Futsal courts, cricket nets, PlayStation lounges, paintball arenas, badminton halls and more. Pick a venue, pick your slot, pay how you like.</p>

        <form action="{{ route('venues.index') }}" method="GET" class="grid w-full max-w-3xl gap-2 rounded-2xl bg-white/95 p-2 shadow-2xl sm:grid-cols-[1fr_1fr_1fr_auto]">
            <label class="flex items-center gap-2 rounded-xl px-3 py-2 text-gray-700">
                <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                <input name="q" type="text" placeholder="Venue or activity" class="w-full bg-transparent text-sm outline-none">
            </label>
            <label class="flex items-center gap-2 rounded-xl px-3 py-2 text-gray-700 sm:border-l border-gray-200">
                <i class="fa-solid fa-medal text-gray-400"></i>
                <select name="activity" class="w-full bg-transparent text-sm outline-none">
                    <option value="">All activities</option>
                    @foreach($activityTypes as $type)
                        <option value="{{ $type->slug }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex items-center gap-2 rounded-xl px-3 py-2 text-gray-700 sm:border-l border-gray-200">
                <i class="fa-solid fa-location-dot text-gray-400"></i>
                <select name="city" class="w-full bg-transparent text-sm outline-none">
                    <option value="">Anywhere</option>
                    @foreach($cities as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="btn-brand !rounded-xl">Search</button>
        </form>
    </div></div>
</section>

{{-- Browse by activity --}}
<section class="mx-auto max-w-6xl px-4 py-14">
    <div class="flex items-end justify-between">
        <div>
            <h2 class="display text-4xl md:text-5xl text-gray-900">Browse by activity</h2>
            <p class="mt-1 text-gray-500">From team sports to gaming stations — every listing is bookable by the block.</p>
        </div>
        <a href="{{ route('venues.index') }}" class="hidden text-sm font-semibold text-brand hover:underline sm:block">See all venues →</a>
    </div>
    <div class="scroll-row mt-8 md:grid md:grid-cols-4 lg:grid-cols-5 md:gap-4 md:overflow-visible">
        @foreach($activityTypes as $type)
            <a href="{{ route('venues.index', ['activity' => $type->slug]) }}"
               class="chip flex w-40 flex-col items-center gap-3 p-5 text-center md:w-auto hover:shadow-md">
                <span class="grid h-14 w-14 place-items-center rounded-2xl text-2xl" style="background: {{ $type->color }}1a; color: {{ $type->color }}">
                    <i class="{{ $type->icon }}"></i>
                </span>
                <span class="font-semibold text-gray-900">{{ $type->name }}</span>
                <span class="text-xs text-gray-500">{{ $type->services_count }} {{ Str::plural('listing', $type->services_count) }}</span>
            </a>
        @endforeach
    </div>
</section>

{{-- Featured venues --}}
<section class="bg-gray-50 py-14">
    <div class="mx-auto max-w-6xl px-4">
        <h2 class="display text-4xl md:text-5xl text-gray-900">Popular venues</h2>
        <p class="mt-1 text-gray-500">Top-rated places our community keeps coming back to.</p>
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($featured as $venue)
                <x-venue-card :venue="$venue" />
            @empty
                <p class="text-gray-500">No venues yet — <a href="{{ route('register.vendor') }}" class="text-brand underline">list yours</a>.</p>
            @endforelse
        </div>
        <div class="mt-8 text-center">
            <a href="{{ route('venues.index') }}" class="btn-ghost">Explore all venues</a>
        </div>
    </div>
</section>

{{-- How it works --}}
<section class="mx-auto max-w-6xl px-4 py-16">
    <h2 class="display text-center text-4xl md:text-5xl text-gray-900">How booking works</h2>
    <div class="mt-10 grid gap-6 md:grid-cols-4">
        @foreach([
            ['fa-solid fa-map-location-dot', 'Pick a venue', 'Filter by activity and city. Every venue shows real opening hours and live availability.'],
            ['fa-solid fa-sliders', 'Build your plan', 'Choose the court, station or seat type, the game if it applies, your start time and how long you play.'],
            ['fa-solid fa-calculator', 'See the price instantly', 'Rates are calculated automatically from the vendor\'s block size, with peak-hour pricing shown line by line.'],
            ['fa-solid fa-hand-holding-dollar', 'Pay your way', 'Pay at the venue, transfer to their bank, or (soon) pay online to lock the slot instantly.'],
        ] as [$icon, $title, $text])
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <span class="grid h-12 w-12 place-items-center rounded-xl bg-brand-soft text-brand text-xl"><i class="{{ $icon }}"></i></span>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ $title }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-500">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- Parallax band --}}
<section class="relative py-32">
    <div class="absolute inset-0 bg-cover bg-center bg-fixed" style="background-image:url(/images/parallaxfinal.jpg)"></div>
    <div class="absolute inset-0 bg-black/60"></div>
    <h2 class="display relative text-center text-4xl text-white sm:text-5xl md:text-6xl">
        <span class="text-red-500">Play</span> · Compete · <span class="text-red-500">Repeat</span>
    </h2>
</section>

{{-- Vendor pitch --}}
<section class="bg-slate-900 py-16 text-white">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 md:grid-cols-2 md:items-center">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[.3em] text-red-400">For venue owners</p>
            <h2 class="display mt-2 text-4xl md:text-5xl">Run any bookable activity from one dashboard</h2>
            <p class="mt-4 text-gray-300">Futsal, cricket, badminton, swimming lanes, PS5 stations, paintball sessions — define your own block size, minimum booking, buffer gaps, seat types and peak-hour pricing. Sportee calculates the rate and keeps your calendar clean.</p>
            <a href="{{ route('register.vendor') }}" class="btn-brand mt-6">List your venue — it's free</a>
        </div>
        <ul class="grid gap-4 sm:grid-cols-2">
            @foreach([
                ['fa-solid fa-clock', 'Flexible blocks', 'Set 30, 60, 90-minute blocks, minimum durations and gaps between bookings.'],
                ['fa-solid fa-couch', 'Seat & unit types', 'Standard vs VIP seats, half vs full court — each with its own price and capacity.'],
                ['fa-solid fa-shield-halved', 'Priority protection', 'Paid bookings automatically outrank pay-at-venue holds, so you never lose a paying customer.'],
                ['fa-solid fa-bell', 'Instant alerts', 'Get notified on every booking, cancellation and bank-transfer slip upload.'],
            ] as [$icon, $title, $text])
                <li class="rounded-2xl bg-slate-800 p-5 ring-1 ring-white/5">
                    <i class="{{ $icon }} text-red-400"></i>
                    <h3 class="mt-3 font-semibold">{{ $title }}</h3>
                    <p class="mt-1 text-sm text-gray-400">{{ $text }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endsection
