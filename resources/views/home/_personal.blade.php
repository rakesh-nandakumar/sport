{{-- Location + personal shortcuts --}}
<section class="mx-auto max-w-6xl px-4 pt-8">
    <x-location-bar :location="$location" />
</section>

{{-- Browse by activity --}}
<section class="mx-auto max-w-6xl px-4 py-12">
    <div class="flex items-end justify-between">
        <div>
            <h2 class="display text-4xl md:text-5xl text-gray-900">Browse by activity</h2>
            <p class="mt-1 text-gray-500">
                @if($location)
                    Sorted by what you play and what's closest to {{ $location['label'] }}.
                @else
                    From team sports to gaming stations — every service is bookable by the block.
                @endif
            </p>
        </div>
        <a href="{{ route('venues.index') }}" class="hidden text-sm font-semibold text-brand hover:underline sm:block">See all venues →</a>
    </div>
    <div class="scroll-row mt-8 md:grid md:grid-cols-3 lg:grid-cols-4 md:gap-4 md:overflow-visible">
        @foreach($activityTypes as $type)
            <a href="{{ route('venues.index', ['activity' => $type->slug]) }}"
               class="activity-tile group relative block w-56 shrink-0 overflow-hidden rounded-2xl md:w-auto" style="--tile-color: {{ $type->color }}">
                @if($type->imageUrl())
                    <img src="{{ $type->imageUrl() }}" alt="{{ $type->name }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-110">
                @else
                    <div class="absolute inset-0" style="background: linear-gradient(135deg, {{ $type->color }}, #0f172a)"></div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-black/10"></div>
                <div class="relative flex h-44 flex-col justify-end p-4 text-white md:h-52">
                    <span class="mb-auto grid h-9 w-9 place-items-center rounded-xl bg-white/15 text-lg backdrop-blur"><i class="{{ $type->icon }}"></i></span>
                    <span class="display text-2xl leading-none md:text-3xl">{{ $type->name }}</span>
                    <span class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-200">
                        {{ $type->services_count }} {{ Str::plural('service', $type->services_count) }}
                        @if($type->nearest_km !== null)<span class="rounded-full bg-white/15 px-2 py-0.5">nearest {{ $type->nearest_km < 1 ? '<1' : round($type->nearest_km) }} km</span>@endif
                        @if($type->affinity > 0)<span class="rounded-full bg-brand/80 px-2 py-0.5 text-white">for you</span>@endif
                    </span>
                </div>
            </a>
        @endforeach
    </div>
</section>

@if($nearby->isNotEmpty())
    {{-- Near you --}}
    <section class="bg-gray-50 py-14">
        <div class="mx-auto max-w-6xl px-4">
            <div class="flex items-end justify-between">
                <div>
                    <h2 class="display text-4xl md:text-5xl text-gray-900">Near {{ $location['label'] }}</h2>
                    <p class="mt-1 text-gray-500">Closest venues to play, within {{ setting('location.nearby_km') }} km.</p>
                </div>
                <a href="{{ route('venues.index', ['sort' => 'nearest']) }}" class="hidden text-sm font-semibold text-brand hover:underline sm:block">All nearby →</a>
            </div>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($nearby as $venue)
                    <x-venue-card :venue="$venue" />
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($bookAgain->isNotEmpty() || $recentlyViewed->isNotEmpty())
    <section class="mx-auto max-w-6xl px-4 py-14">
        <div class="grid gap-10 lg:grid-cols-2">
            @if($bookAgain->isNotEmpty())
                <div>
                    <h2 class="display text-3xl md:text-4xl text-gray-900">Book again</h2>
                    <p class="mt-1 text-gray-500">Your usual spots, one tap away.</p>
                    <ul class="mt-4 space-y-3">
                        @foreach($bookAgain as $b)
                            <li class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
                                <img src="{{ $b->service->imageUrl() }}" alt="" class="h-16 w-20 rounded-xl object-cover">
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: {{ $b->service->activityType->color }}">{{ $b->service->activityType->name }}</p>
                                    <p class="truncate font-semibold text-gray-900">{{ $b->service->name }} <span class="font-normal text-gray-500">· {{ $b->service->venue->name }}</span></p>
                                    <p class="text-xs text-gray-500">Last time: {{ $b->starts_at->format('D d M, h:i A') }} · {{ $b->option->name }}</p>
                                </div>
                                <a href="{{ route('booking.build', $b->service) }}" class="btn-brand !px-4 !py-2 text-sm">Book</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if($recentlyViewed->isNotEmpty())
                <div>
                    <h2 class="display text-3xl md:text-4xl text-gray-900">Recently viewed</h2>
                    <p class="mt-1 text-gray-500">Pick up where you left off.</p>
                    <ul class="mt-4 space-y-3">
                        @foreach($recentlyViewed as $venue)
                            @php($km = $location ? $venue->distanceFrom($location['lat'], $location['lng']) : null)
                            <li>
                                <a href="{{ route('venues.show', $venue) }}" class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm hover:border-gray-400">
                                    <img src="{{ $venue->coverUrl() }}" alt="" class="h-16 w-20 rounded-xl object-cover">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-semibold text-gray-900">{{ $venue->name }}</p>
                                        <p class="text-xs text-gray-500"><i class="fa-solid fa-location-dot mr-1"></i>{{ $venue->city }}@if($km !== null) · {{ $km }} km away @endif</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $venue->services->pluck('activityType.name')->unique()->take(3)->join(', ') }}</p>
                                    </div>
                                    <i class="fa-solid fa-chevron-right text-gray-300"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>
@endif

{{-- Featured venues --}}
<section class="{{ $nearby->isNotEmpty() ? 'bg-white' : 'bg-gray-50' }} py-14">
    <div class="mx-auto max-w-6xl px-4">
        <h2 class="display text-4xl md:text-5xl text-gray-900">Popular venues</h2>
        <p class="mt-1 text-gray-500">Top-rated venues our community keeps coming back to.</p>
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
