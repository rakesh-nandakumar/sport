{{-- One bookable service on the venue page: every rate and today's start-time grid, straight from the listing. --}}
@php($slots = $todaySlots[$service->id] ?? collect())
@php($openToday = $service->windowFor(today()->dayOfWeek))
<div class="flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
    @if($service->image)
        <img src="{{ $service->imageUrl() }}" alt="" class="h-36 w-full object-cover">
    @endif
    <div class="flex flex-1 flex-col p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h4 class="font-semibold text-gray-900">{{ $service->name }}</h4>
                <p class="text-xs text-gray-500">{{ $service->slotLabel() }} blocks · min {{ minutes_label($service->min_slots * $service->slot_minutes) }}@if($service->max_slots) · max {{ minutes_label($service->max_slots * $service->slot_minutes) }}@endif @if($service->max_players) · up to {{ $service->max_players }} players @endif</p>
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

        {{-- Rates: every option and every peak/off-peak multiplier --}}
        <div class="mt-3 overflow-hidden rounded-xl border border-gray-100 text-xs">
            <table class="w-full">
                <tbody class="divide-y divide-gray-100">
                    @foreach($service->options as $opt)
                        <tr>
                            <td class="px-3 py-1.5 text-gray-700">{{ $opt->name }}@if($opt->capacity > 1) <span class="text-gray-400">· {{ $opt->capacity }} units</span>@endif</td>
                            <td class="px-3 py-1.5 text-right font-semibold text-gray-900">{{ lkr($opt->price_per_slot) }} <span class="font-normal text-gray-400">/ {{ $service->slotLabel() }}</span></td>
                        </tr>
                    @endforeach
                    @foreach($service->rates as $rate)
                        <tr class="bg-amber-50/60">
                            <td class="px-3 py-1.5 text-amber-900"><i class="fa-solid fa-bolt mr-1"></i>{{ $rate->name }} <span class="text-amber-700">· {{ collect($rate->days)->map(fn ($d) => substr(\App\Models\VenueHour::DAYS[$d], 0, 3))->join(', ') }} {{ substr($rate->starts_at, 0, 5) }}–{{ substr($rate->ends_at, 0, 5) }}</span></td>
                            <td class="px-3 py-1.5 text-right font-semibold {{ $rate->multiplier > 1 ? 'text-amber-900' : 'text-emerald-700' }}">{{ $rate->multiplier > 1 ? '+' : '−' }}{{ round(abs($rate->multiplier - 1) * 100) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($service->games->isNotEmpty())
            <p class="mt-2 text-xs text-gray-500"><i class="fa-solid fa-gamepad mr-1"></i>{{ $service->games->pluck('name')->take(4)->join(', ') }}{{ $service->games->count() > 4 ? ' +'.($service->games->count() - 4).' more' : '' }}</p>
        @endif

        {{-- Today's start times --}}
        <div class="mt-3">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Today{{ $openToday ? ' · '.$openToday[0].' – '.$openToday[1] : '' }}</p>
            @if(! $openToday)
                <p class="mt-1 text-xs text-gray-400">Closed today — pick another day on the booking page.</p>
            @elseif($slots->isEmpty())
                <p class="mt-1 text-xs text-gray-400">No slots today.</p>
            @else
                <div class="mt-1.5 flex flex-wrap gap-1">
                    @foreach($slots as $slot)
                        <span class="rounded-md px-1.5 py-0.5 text-[11px] {{ $slot['bookable'] ? 'bg-emerald-50 text-emerald-800' : 'bg-gray-100 text-gray-400 line-through' }}" title="{{ $slot['bookable'] ? ($slot['free'].' free · up to '.minutes_label($slot['max_blocks'] * $service->slot_minutes)) : 'Unavailable' }}">{{ $slot['start']->format('g:i') }}</span>
                    @endforeach
                </div>
                <p class="mt-1 text-[11px] text-gray-400">{{ $slots->where('bookable', true)->count() }} of {{ $slots->count() }} start times open today · {{ $service->defaultOption()?->name }}</p>
            @endif
        </div>

        <a href="{{ route('booking.build', $service) }}" class="btn-brand mt-4 w-full">Book now</a>
    </div>
</div>
