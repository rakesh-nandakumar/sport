<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
    <div class="min-w-0 space-y-8">
        {{-- Step 1: option --}}
        <section>
            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                <span class="grid h-7 w-7 place-items-center rounded-full bg-gray-900 text-xs text-white">1</span>
                Choose your {{ Str::lower($service->activityType->unit_label) }} type
            </h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach($service->options as $opt)
                    <button type="button" wire:click="$set('optionId', {{ $opt->id }})"
                            class="chip flex items-center justify-between p-4 text-left {{ $optionId === $opt->id ? 'is-selected' : '' }}">
                        <span>
                            <span class="block font-semibold text-gray-900">{{ $opt->name }}</span>
                            @if($opt->description)<span class="block text-xs text-gray-500">{{ $opt->description }}</span>@endif
                            <span class="block text-xs text-gray-400">{{ $opt->capacity }} {{ Str::plural('unit', $opt->capacity) }} available per slot</span>
                        </span>
                        <span class="text-right">
                            <span class="block font-semibold text-gray-900">{{ lkr($opt->price_per_slot) }}</span>
                            <span class="block text-[11px] text-gray-400">per {{ $service->slotLabel() }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- Step 2: game --}}
        @if($service->requiresGame())
            <section>
                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                    <span class="grid h-7 w-7 place-items-center rounded-full bg-gray-900 text-xs text-white">2</span>
                    Pick your game
                </h2>
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                    @foreach($service->games as $game)
                        <button type="button" wire:click="$set('gameId', {{ $game->id }})"
                                class="chip p-3 text-left {{ $gameId === $game->id ? 'is-selected' : '' }}">
                            <i class="fa-solid fa-gamepad text-gray-400"></i>
                            <span class="mt-1 block text-sm font-semibold text-gray-900 leading-tight">{{ $game->name }}</span>
                            <span class="block text-[11px] text-gray-500">{{ $game->platform }}@if($game->max_players) · {{ $game->max_players }}P @endif</span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Step 3: date --}}
        <section>
            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                <span class="grid h-7 w-7 place-items-center rounded-full bg-gray-900 text-xs text-white">{{ $service->requiresGame() ? 3 : 2 }}</span>
                Pick a date
            </h2>
            <div class="scroll-row mt-3">
                @foreach($days as $d)
                    <button type="button" wire:click="selectDate('{{ $d['date'] }}')" @disabled(! $d['open'])
                            class="chip w-16 py-2 text-center {{ $date === $d['date'] ? 'is-selected' : '' }} {{ ! $d['open'] ? 'is-disabled' : '' }}">
                        <span class="block text-[11px] uppercase text-gray-500">{{ $d['today'] ? 'Today' : $d['dow'] }}</span>
                        <span class="block text-xl font-semibold text-gray-900">{{ $d['day'] }}</span>
                        <span class="block text-[11px] text-gray-500">{{ $d['month'] }}</span>
                    </button>
                @endforeach
            </div>
            <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-500">
                <i class="fa-regular fa-calendar"></i> or pick another date
                <input type="date" wire:model.live="date" min="{{ today()->toDateString() }}" class="rounded-lg border border-gray-200 px-2 py-1 text-sm">
            </label>
        </section>

        {{-- Step 4: time --}}
        <section>
            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                <span class="grid h-7 w-7 place-items-center rounded-full bg-gray-900 text-xs text-white">{{ $service->requiresGame() ? 4 : 3 }}</span>
                Choose a start time
                @if($window)<span class="text-sm font-normal text-gray-500">· open {{ $window[0] }} – {{ $window[1] }}</span>@endif
            </h2>
            @if($timeSlots->isEmpty())
                <p class="mt-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">The venue is closed on this date. Pick another day.</p>
            @else
                <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                    @foreach($timeSlots as $slot)
                        <button type="button" wire:click="selectTime('{{ $slot['start']->format('H:i') }}')" @disabled(! $slot['available'])
                                class="chip px-2 py-2.5 text-center text-sm font-medium {{ $startTime === $slot['start']->format('H:i') ? 'is-selected' : '' }} {{ ! $slot['available'] ? 'is-disabled' : '' }}">
                            {{ $slot['label'] }}
                            @if($slot['available'] && $option->capacity > 1)
                                <span class="block text-[10px] font-normal text-gray-400">{{ $slot['free'] }} left</span>
                            @endif
                        </button>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-gray-400">Greyed-out times are booked, past, or inside the venue's {{ $service->lead_time_minutes }}-minute notice period.</p>
            @endif
        </section>

        {{-- Step 5: duration --}}
        <section>
            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                <span class="grid h-7 w-7 place-items-center rounded-full bg-gray-900 text-xs text-white">{{ $service->requiresGame() ? 5 : 4 }}</span>
                How long do you want to play?
            </h2>
            <div class="mt-3 flex flex-wrap items-center gap-4">
                <div class="inline-flex items-center rounded-xl border border-gray-200">
                    <button type="button" wire:click="decrementSlots" class="px-4 py-2 text-lg text-gray-600 hover:bg-gray-50 disabled:opacity-30" @disabled($blocks <= $service->min_slots)>−</button>
                    <span class="min-w-[120px] px-3 text-center font-semibold text-gray-900">{{ minutes_label($blocks * $service->slot_minutes) }}</span>
                    <button type="button" wire:click="incrementSlots" class="px-4 py-2 text-lg text-gray-600 hover:bg-gray-50 disabled:opacity-30" @disabled($startTime && $blocks >= $maxSlots)>+</button>
                </div>
                <p class="text-sm text-gray-500">
                    Blocks of {{ $service->slotLabel() }} · minimum {{ minutes_label($service->min_slots * $service->slot_minutes) }}
                    @if($startTime) · up to {{ minutes_label($maxSlots * $service->slot_minutes) }} from {{ \Carbon\Carbon::parse($startTime)->format('h:i A') }} @endif
                </p>
            </div>
            @if($service->max_players)
                <label class="mt-4 block max-w-xs text-sm text-gray-600">
                    Number of players <span class="text-gray-400">(optional, max {{ $service->max_players }})</span>
                    <input type="number" min="1" max="{{ $service->max_players }}" wire:model.live="players" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2">
                </label>
            @endif
        </section>
    </div>

    {{-- Summary --}}
    <aside class="sticky-summary min-w-0 -mx-4 px-4 py-4 lg:static lg:mx-0 lg:self-start lg:rounded-2xl lg:border lg:border-gray-200 lg:p-6 lg:shadow-lg lg:sticky lg:top-28">
        <h3 class="display hidden text-3xl text-gray-900 lg:block">Your plan</h3>
        <dl class="mt-2 hidden space-y-2 text-sm lg:block">
            <div class="flex justify-between"><dt class="text-gray-500">Venue</dt><dd class="font-medium text-right">{{ $service->venue->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Service</dt><dd class="font-medium text-right">{{ $service->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Type</dt><dd class="font-medium text-right">{{ $option->name }}</dd></div>
            @if($gameId)
                <div class="flex justify-between"><dt class="text-gray-500">Game</dt><dd class="font-medium text-right">{{ $service->games->firstWhere('id', $gameId)?->name }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-gray-500">Date</dt><dd class="font-medium text-right">{{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Time</dt><dd class="font-medium text-right">{{ $startTime ? \Carbon\Carbon::parse($startTime)->format('h:i A').' – '.$endsAt->format('h:i A') : '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Duration</dt><dd class="font-medium text-right">{{ minutes_label($blocks * $service->slot_minutes) }}</dd></div>
        </dl>

        @if($quote)
            <div class="mt-4 hidden space-y-1 border-t border-gray-100 pt-3 text-sm lg:block">
                @foreach($quote['lines'] as $line)
                    <div class="flex justify-between text-gray-600"><span>{{ $line['label'] }}</span><span>{{ lkr($line['amount']) }}</span></div>
                @endforeach
            </div>
        @endif

        <div class="flex items-center justify-between gap-4 lg:mt-4 lg:border-t lg:border-gray-100 lg:pt-4">
            <div>
                <p class="text-xs text-gray-500 lg:text-sm">{{ $startTime ? \Carbon\Carbon::parse($date.' '.$startTime)->format('D d M · h:i A') : 'Pick a time' }}</p>
                <p class="text-2xl font-bold text-gray-900">{{ $quote ? lkr($quote['total']) : '—' }}</p>
            </div>
            <button type="button" wire:click="checkout" wire:loading.attr="disabled" class="btn-brand">
                <span wire:loading.remove wire:target="checkout">Checkout <i class="fa-solid fa-arrow-right"></i></span>
                <span wire:loading wire:target="checkout"><i class="fa-solid fa-spinner fa-spin"></i> Please wait</span>
            </button>
        </div>
        @if($error)
            <p class="mt-2 text-sm text-rose-600"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $error }}</p>
        @endif
        <p class="mt-2 hidden text-xs text-gray-400 lg:block">You'll choose how to pay on the next step. Nothing is charged yet.</p>
    </aside>
</div>
