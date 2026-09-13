@extends('layouts.app')
@section('title', $venue->name.' · Sportee')
@section('main-class', '')

@section('content')
{{-- Hero --}}
<section class="relative h-[60vh] min-h-[380px] overflow-hidden bg-cover bg-center" style="background-image:url('{{ $venue->coverUrl() }}')">
    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-black/30"></div>
    <div class="relative mx-auto flex h-full max-w-6xl flex-col justify-end px-4 pb-8 pt-28 text-white">
        <div class="flex flex-wrap gap-2">
            @foreach($venue->services->pluck('activityType')->unique('id') as $type)
                <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur"><i class="{{ $type->icon }} mr-1"></i>{{ $type->name }}</span>
            @endforeach
        </div>
        <h1 class="display mt-3 text-5xl md:text-7xl">{{ $venue->name }}</h1>
        <p class="mt-1 text-gray-200"><i class="fa-solid fa-location-dot mr-1"></i>{{ $venue->address }}, {{ $venue->city }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-gray-200">
            @if($venue->reviews->count())
                <span><i class="fa-solid fa-star text-amber-400"></i> {{ $venue->averageRating() }} · {{ $venue->reviews->count() }} reviews</span>
            @endif
            <a href="tel:{{ $venue->phone }}" class="hover:underline"><i class="fa-solid fa-phone mr-1"></i>{{ $venue->phone }}</a>
            @if($venue->website)
                <a href="{{ $venue->website }}" target="_blank" rel="noopener" class="hover:underline"><i class="fa-solid fa-globe mr-1"></i>Website</a>
            @endif
        </div>
    </div>
</section>

<section class="mx-auto grid max-w-6xl gap-10 px-4 py-12 lg:grid-cols-[1fr_320px]">
    <div>
        {{-- Services --}}
        <h2 class="display text-4xl text-gray-900">What you can book</h2>
        <p class="text-gray-500">Choose a service to build your booking. Prices are per block; the exact total is calculated as you pick your time.</p>

        @forelse($servicesByActivity as $activity => $services)
            <div class="mt-8">
                <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                    <i class="{{ $services->first()->activityType->icon }}" style="color: {{ $services->first()->activityType->color }}"></i>{{ $activity }}
                </h3>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    @foreach($services as $service)
                        <div class="flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                            @if($service->image)
                                <img src="{{ $service->imageUrl() }}" alt="" class="h-36 w-full object-cover">
                            @endif
                            <div class="flex flex-1 flex-col p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h4 class="font-semibold text-gray-900">{{ $service->name }}</h4>
                                        <p class="text-xs text-gray-500">{{ $service->slotLabel() }} blocks · min {{ minutes_label($service->min_slots * $service->slot_minutes) }}@if($service->max_players) · up to {{ $service->max_players }} players @endif</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500">from</p>
                                        <p class="font-semibold text-gray-900">{{ lkr($service->priceFrom()) }}</p>
                                        <p class="text-[11px] text-gray-400">per {{ $service->slotLabel() }}</p>
                                    </div>
                                </div>
                                @if($service->description)
                                    <p class="mt-2 text-sm text-gray-600 line-clamp-2">{{ $service->description }}</p>
                                @endif
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach($service->options as $opt)
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-700">{{ $opt->name }} · {{ lkr($opt->price_per_slot) }}</span>
                                    @endforeach
                                </div>
                                @if($service->games->isNotEmpty())
                                    <p class="mt-2 text-xs text-gray-500"><i class="fa-solid fa-gamepad mr-1"></i>{{ $service->games->pluck('name')->take(4)->join(', ') }}{{ $service->games->count() > 4 ? ' +'.($service->games->count() - 4).' more' : '' }}</p>
                                @endif
                                <a href="{{ route('booking.build', $service) }}" class="btn-brand mt-4 w-full">Book now</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="mt-6 rounded-xl bg-amber-50 p-4 text-amber-800">This venue hasn't published any services yet.</p>
        @endforelse

        {{-- About --}}
        @if($venue->description)
            <div class="mt-12">
                <h2 class="display text-4xl text-gray-900">About the venue</h2>
                <p class="mt-3 whitespace-pre-line leading-relaxed text-gray-600">{{ $venue->description }}</p>
            </div>
        @endif

        {{-- Reviews --}}
        <div class="mt-12">
            <h2 class="display text-4xl text-gray-900">Reviews</h2>
            @forelse($venue->reviews as $review)
                <div class="mt-4 rounded-2xl border border-gray-100 bg-gray-50 p-4">
                    <div class="flex items-center justify-between">
                        <p class="font-semibold text-gray-900">{{ $review->user->name }}</p>
                        <p class="text-sm text-amber-500">
                            @for($i = 1; $i <= 5; $i++)<i class="fa-{{ $i <= $review->rating ? 'solid' : 'regular' }} fa-star"></i>@endfor
                        </p>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">{{ $review->comment }}</p>
                    <p class="mt-2 text-xs text-gray-400">{{ $review->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <p class="mt-3 text-gray-500">No reviews yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Sidebar --}}
    <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-gray-900"><i class="fa-regular fa-clock mr-2 text-brand"></i>Opening hours</h3>
            <ul class="mt-3 divide-y divide-gray-100 text-sm">
                @foreach($venue->hours as $h)
                    <li class="flex justify-between py-1.5 {{ $h->day_of_week === now()->dayOfWeek ? 'font-semibold text-gray-900' : 'text-gray-600' }}">
                        <span>{{ $h->dayName() }}</span><span>{{ $h->label() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        @if($venue->amenities)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-gray-900"><i class="fa-solid fa-star mr-2 text-brand"></i>Amenities</h3>
                <ul class="mt-3 grid grid-cols-2 gap-2 text-sm text-gray-600">
                    @foreach($venue->amenities as $a)
                        <li><i class="fa-solid fa-check mr-1 text-emerald-500"></i>{{ $a }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm text-sm">
            <h3 class="font-semibold text-gray-900"><i class="fa-solid fa-address-card mr-2 text-brand"></i>Contact</h3>
            <p class="mt-3 text-gray-600">{{ $venue->address }}<br>{{ $venue->city }}@if($venue->district), {{ $venue->district }}@endif</p>
            <p class="mt-2"><a href="tel:{{ $venue->phone }}" class="text-brand hover:underline">{{ $venue->phone }}</a></p>
            @if($venue->email)<p><a href="mailto:{{ $venue->email }}" class="text-brand hover:underline">{{ $venue->email }}</a></p>@endif
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($venue->name.' '.$venue->address.' '.$venue->city) }}" target="_blank" rel="noopener" class="btn-ghost mt-4 w-full">Open in Google Maps</a>
        </div>
    </aside>
</section>
@endsection
