@extends('layout')
@section('content')

<!-- Jumbotron -->
<div class="relative h-[420px] overflow-hidden bg-cover bg-center sm:h-[500px]"
     style="background-image:url('{{ $indoors->photo ? asset('storage/' . $indoors->photo) : asset('/images/CR7.png') }}');">
    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/45 to-black/10"></div>
    <div class="relative flex h-full items-end pb-10">
        <div class="mx-auto w-full max-w-6xl px-6">
            @if ($indoors->activities->isNotEmpty())
                <div class="mb-4 flex flex-wrap gap-2">
                    @foreach ($indoors->activities as $activity)
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-white ring-1 ring-white/30 backdrop-blur">
                            {{ $activity->name }}
                        </span>
                    @endforeach
                </div>
            @endif
            <h1 class="font-display text-4xl tracking-wide text-white sm:text-6xl">{{ $indoors->title }}</h1>
            <p class="mt-3 flex items-center gap-2 text-white/80"><i class="fa-solid fa-location-dot"></i> {{ $indoors->location }}</p>
        </div>
    </div>
</div>
<!-- /Jumbotron -->

<!-- Sticky sub-nav -->
<div class="sticky z-40 border-b border-gray-100 bg-white/90 backdrop-blur" style="top: var(--header-h);">
    <div class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-6 py-3">
        <a href="#book" class="nav-link">Bookings</a>
        <a href="#location" class="nav-link">Location</a>
        <a href="#about" class="nav-link">About</a>
        <a href="#gallery" class="nav-link">Gallery</a>
        <a href="#reviews" class="nav-link">Reviews</a>
    </div>
</div>

<section id="location" class="bg-white py-16">
    <div class="mx-auto max-w-6xl px-6">
        <div class="grid gap-10 lg:grid-cols-2">
            <div>
                <span class="eyebrow">Opening hours</span>
                <h2 class="text-2xl font-bold text-gray-900">Location &amp; Opening Times</h2>
                <p class="mt-3 text-gray-600">{{ $indoors->location }}</p>

                <ul class="mt-6 divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-100">
                    @php
                        $dayNames = ['sun' => 'sunday', 'mon' => 'monday', 'tue' => 'tuesday', 'wed' => 'wednesday', 'thu' => 'thursday', 'fri' => 'friday', 'sat' => 'saturday'];
                    @endphp
                    @foreach ($dayNames as $short => $day)
                        @php
                            $hours = $indoors->hoursFor(\Carbon\Carbon::parse('next ' . $day));
                        @endphp
                        <li class="flex items-center justify-between px-4 py-3 text-sm">
                            <span class="font-medium text-gray-700">{{ ucfirst($day) }}</span>
                            @if ($hours)
                                <span class="text-gray-900">{{ date('h:i A', strtotime($hours['open'])) }} &ndash; {{ date('h:i A', strtotime($hours['close'])) }}</span>
                            @else
                                <span class="font-medium text-red-500">Closed</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="overflow-hidden rounded-2xl shadow-sm ring-1 ring-gray-100">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.73325733986!2d79.85863481076612!3d6.9224568183693425!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae25912f6322a05%3A0x1ff3379353128405!2sExcel%20World%20Entertainment%20Park!5e0!3m2!1sen!2slk!4v1710947863099!5m2!1sen!2slk" class="h-full min-h-[320px] w-full" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</section>

<section id="about" class="bg-gray-50 py-16">
    <div class="mx-auto max-w-6xl px-6">
        <span class="eyebrow">About this venue</span>
        <h2 class="text-2xl font-bold text-gray-900">About</h2>
        <p class="mt-4 max-w-3xl leading-relaxed text-gray-600">{{ $indoors->description }}</p>
    </div>
</section>

<section id="gallery" class="bg-white py-16">
    <div class="mx-auto max-w-6xl px-6">
        <span class="eyebrow">Take a look around</span>
        <h2 class="mb-8 text-2xl font-bold text-gray-900">Facility Overview</h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
            @foreach($gallery as $image)
                <div class="aspect-[4/3] overflow-hidden rounded-xl bg-gray-100">
                    <img src="{{ asset($image) }}" alt="{{ $indoors->title }} gallery photo" class="h-full w-full object-cover transition-transform duration-300 hover:scale-105">
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="book" class="bg-gray-50 py-16">
    @auth
        @livewire('booking-widget', ['indoor' => $indoors])
    @else
        <div class="mx-auto max-w-2xl rounded-2xl border border-gray-100 bg-white p-10 text-center shadow-sm">
            <p class="text-lg font-medium text-gray-600">Please <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">login</a> to book.</p>
        </div>
    @endauth
</section>

<section id="reviews">
    @include('partials._comments')
</section>

@endsection
