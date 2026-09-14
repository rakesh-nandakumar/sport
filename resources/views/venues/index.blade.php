@extends('layouts.app')
@section('title', 'Venues · EntryPoint.lk')

@section('content')
<section class="mx-auto max-w-6xl px-4 pb-16">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="display text-4xl md:text-5xl text-gray-900">Find a venue to play</h1>
            <p class="mt-1 text-gray-500">{{ $venues->total() }} {{ Str::plural('venue', $venues->total()) }} {{ $filters['activity'] ?? false ? 'offering '.$activityTypes->firstWhere('slug', $filters['activity'])?->name : '' }}{{ $filters['city'] ?? false ? ' in '.$filters['city'] : '' }}</p>
        </div>
    </div>

    <div class="mt-6"><x-location-bar :location="$location" /></div>

    <form method="GET" class="mt-4 grid gap-2 rounded-2xl border border-gray-200 bg-white p-2 shadow-sm sm:grid-cols-[1fr_1fr_1fr_auto_auto]">
        <label class="flex items-center gap-2 rounded-xl px-3 py-2">
            <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Venue or service name" class="w-full text-sm outline-none">
        </label>
        <label class="flex items-center gap-2 rounded-xl px-3 py-2 sm:border-l border-gray-200">
            <i class="fa-solid fa-medal text-gray-400"></i>
            <select name="activity" class="w-full bg-transparent text-sm outline-none">
                <option value="">All activities</option>
                @foreach($activityTypes as $type)
                    <option value="{{ $type->slug }}" @selected(($filters['activity'] ?? '') === $type->slug)>{{ $type->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex items-center gap-2 rounded-xl px-3 py-2 sm:border-l border-gray-200">
            <i class="fa-solid fa-location-dot text-gray-400"></i>
            <select name="city" class="w-full bg-transparent text-sm outline-none">
                <option value="">Anywhere</option>
                @foreach($cities as $city)
                    <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex items-center gap-2 rounded-xl px-3 py-2 sm:border-l border-gray-200">
            <i class="fa-solid fa-arrow-down-wide-short text-gray-400"></i>
            <select name="sort" class="w-full bg-transparent text-sm outline-none">
                @if($location)<option value="nearest" @selected($sort === 'nearest')>Nearest first</option>@endif
                <option value="featured" @selected($sort === 'featured')>Featured</option>
                <option value="rating" @selected($sort === 'rating')>Top rated</option>
                <option value="name" @selected($sort === 'name')>Name A–Z</option>
            </select>
        </label>
        <button class="btn-brand !rounded-xl">Filter</button>
    </form>

    <div class="scroll-row mt-4">
        <a href="{{ route('venues.index', array_filter(['q' => $filters['q'] ?? null, 'city' => $filters['city'] ?? null, 'sort' => $sort])) }}"
           class="chip px-4 py-2 text-sm font-medium {{ empty($filters['activity']) ? 'is-selected' : '' }}">All</a>
        @foreach($activityTypes as $type)
            <a href="{{ route('venues.index', array_filter(['q' => $filters['q'] ?? null, 'city' => $filters['city'] ?? null, 'activity' => $type->slug, 'sort' => $sort])) }}"
               class="chip px-4 py-2 text-sm font-medium {{ ($filters['activity'] ?? '') === $type->slug ? 'is-selected' : '' }}">
                <i class="{{ $type->icon }} mr-1" style="color: {{ $type->color }}"></i>{{ $type->name }}
            </a>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($venues as $venue)
            <x-venue-card :venue="$venue" />
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-12 text-center text-gray-500">
                <i class="fa-regular fa-face-frown text-3xl"></i>
                <p class="mt-3">No venues match those filters yet.</p>
                <a href="{{ route('venues.index') }}" class="mt-4 inline-block text-brand underline">Clear filters</a>
            </div>
        @endforelse
    </div>

    <div class="mt-8">{{ $venues->links() }}</div>
</section>
@endsection
