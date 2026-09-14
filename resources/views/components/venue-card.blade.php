@props(['venue'])

@php
    $activities = $venue->services->pluck('activityType')->unique('id')->take(4);
    $from = $venue->services->flatMap->options->min('price_per_slot');
    $rating = $venue->reviews_avg_rating ?? null;
    $km = $venue->distance_km ?? null;
@endphp

<article class="group overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-gray-100 transition hover:-translate-y-1 hover:shadow-xl">
    <a href="{{ route('venues.show', $venue) }}" class="block">
        <div class="relative h-44 overflow-hidden">
            <img class="h-full w-full object-cover transition duration-500 group-hover:scale-105" src="{{ $venue->coverUrl() }}" alt="{{ $venue->name }}">
            @if($venue->is_featured)
                <span class="absolute left-3 top-3 rounded-full bg-brand px-3 py-1 text-xs font-semibold text-white shadow">Featured</span>
            @endif
            @if($rating)
                <span class="absolute right-3 top-3 rounded-full bg-black/70 px-2.5 py-1 text-xs font-semibold text-white"><i class="fa-solid fa-star text-amber-400 mr-1"></i>{{ number_format($rating, 1) }}</span>
            @endif
        </div>
        <div class="p-4">
            <p class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-brand"><span><i class="fa-solid fa-location-dot mr-1"></i>{{ $venue->city }}</span>@if($km !== null)<span class="rounded-full bg-gray-100 px-2 py-0.5 normal-case tracking-normal text-gray-600">{{ $km < 1 ? '<1' : $km }} km away</span>@endif</p>
            <h3 class="mt-1 text-lg font-semibold text-gray-900 leading-snug">{{ $venue->name }}</h3>
            @if($venue->tagline)
                <p class="mt-1 text-sm text-gray-500 line-clamp-1">{{ $venue->tagline }}</p>
            @endif
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach($activities as $type)
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium" style="background: {{ $type->color }}1a; color: {{ $type->color }}">
                        <i class="{{ $type->icon }}"></i>{{ $type->name }}
                    </span>
                @endforeach
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3">
                <span class="text-sm text-gray-500">{{ $venue->services->count() }} {{ Str::plural('service', $venue->services->count()) }}</span>
                @if($from !== null)
                    <span class="text-sm font-semibold text-gray-900">from {{ lkr($from) }}</span>
                @endif
            </div>
        </div>
    </a>
</article>
