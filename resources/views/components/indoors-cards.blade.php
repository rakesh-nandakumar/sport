@props(['item'])

<article class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl">
    <a href="/home/{{ $item->id }}" class="block">
        <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
            <img class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                 src="{{ $item->photo ? asset('storage/' . $item->photo) : asset('/images/CR7.png') }}"
                 alt="{{ $item->title }}" />
            <div class="absolute inset-0 bg-gradient-to-t from-black/35 via-transparent to-transparent"></div>

            @if ($item->resources->isNotEmpty())
                <span class="absolute right-3 top-3 rounded-full bg-white/95 px-3 py-1 text-xs font-semibold text-brand-700 shadow">
                    From Rs {{ number_format($item->resources->min('rate'), 0) }}
                </span>
            @endif
        </div>

        <div class="p-4 pb-2">
            <p class="flex items-center gap-1.5 text-xs font-medium text-brand-600">
                <i class="fa-solid fa-location-dot"></i> {{ $item->location }}
            </p>
            <h3 class="mt-1 text-lg font-semibold leading-snug text-gray-900 group-hover:text-brand-700">
                {{ $item->title }}
            </h3>
        </div>
    </a>

    <div class="flex flex-wrap items-center gap-1.5 px-4 pb-4 pt-1">
        <x-listing-tags :indoor="$item"/>
    </div>
</article>
