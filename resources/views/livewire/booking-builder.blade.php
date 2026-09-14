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
            @if($service->rates->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2 text-xs text-gray-500">
                    @foreach($service->rates as $rate)
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-800"><i class="fa-solid fa-bolt mr-1"></i>{{ $rate->name }} · {{ collect($rate->days)->map(fn ($d) => substr(\App\Models\VenueHour::DAYS[$d], 0, 3))->join(', ') }} {{ substr($rate->starts_at, 0, 5) }}–{{ substr($rate->ends_at, 0, 5) }} · ×{{ rtrim(rtrim(number_format($rate->multiplier, 2), '0'), '.') }}</span>
                    @endforeach
                </div>
            @endif
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
                <input type="date" wire:model.live="date" min="{{ today()->toDateString() }}" max="{{ today()->addDays((int) setting('bookings.max_days_ahead'))->toDateString() }}" class="rounded-lg border border-gray-200 px-2 py-1 text-sm">
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
                        @php($isSelected = $startAt === $slot['start']->toDateTimeString())
                        <button type="button" wire:click="selectTime('{{ $slot['start']->toDateTimeString() }}')" @disabled(! $slot['bookable'])
                                title="{{ $slot['bookable'] ? 'Up to '.minutes_label($slot['max_blocks'] * $service->slot_minutes).' from here' : ($slot['available'] ? 'Not enough free time after this slot for the minimum booking' : 'Unavailable') }}"
                                class="chip px-2 py-2.5 text-center text-sm font-medium {{ $isSelected ? 'is-selected' : '' }} {{ ! $slot['bookable'] ? 'is-disabled' : '' }}">
                            {{ $slot['label'] }}@if(! $slot['start']->isSameDay(\Carbon\Carbon::parse($date)))<span class="text-[10px] text-gray-400"> +1</span>@endif
                            @if($slot['bookable'] && $option->capacity > 1)
                                <span class="block text-[10px] font-normal text-gray-400">{{ $slot['free'] }} left</span>
                            @elseif($slot['bookable'] && $slot['max_blocks'] < ($service->max_slots ?? 99))
                                <span class="block text-[10px] font-normal text-gray-400">max {{ minutes_label($slot['max_blocks'] * $service->slot_minutes) }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-gray-400">Greyed-out times are booked, past, inside the venue's {{ minutes_label($service->lead_time_minutes) }} notice period, or don't leave enough free time for the {{ minutes_label($service->min_slots * $service->slot_minutes) }} minimum.</p>
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
                    <button type="button" wire:click="incrementSlots" class="px-4 py-2 text-lg text-gray-600 hover:bg-gray-50 disabled:opacity-30" @disabled($startAt && $blocks >= $maxSlots)>+</button>
                </div>
                <p class="text-sm text-gray-500">
                    Blocks of {{ $service->slotLabel() }} · minimum {{ minutes_label($service->min_slots * $service->slot_minutes) }}
                    @if($startsAt) · up to {{ minutes_label($maxSlots * $service->slot_minutes) }} from {{ $startsAt->format('h:i A') }} @endif
                </p>
            </div>
            @if($startsAt && $maxSlots < ($service->max_slots ?? 99))
                <p class="mt-2 text-xs text-amber-700"><i class="fa-solid fa-circle-info mr-1"></i>Another booking (or closing time) follows this slot, so the longest you can book from {{ $startsAt->format('h:i A') }} is {{ minutes_label($maxSlots * $service->slot_minutes) }}.</p>
            @endif
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
            <div class="flex justify-between"><dt class="text-gray-500">Time</dt><dd class="font-medium text-right">{{ $startsAt ? $startsAt->format('h:i A').' – '.$endsAt->format('h:i A') : '—' }}</dd></div>
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
                <p class="text-xs text-gray-500 lg:text-sm">{{ $startsAt ? $startsAt->format('D d M · h:i A') : 'Pick a time' }}</p>
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
        <p class="mt-2 hidden text-xs text-gray-400 lg:block">Checkout opens the payment options. Nothing is charged until you confirm.</p>
    </aside>

    {{-- Checkout / payment modal --}}
    @if($showCheckout && $quote)
        <div class="fixed inset-0 z-[100000] flex items-end justify-center bg-black/60 p-0 sm:items-center sm:p-4"
             wire:click.self="closeCheckout"
             x-data="{ init() { document.body.classList.add('overflow-hidden') }, destroy() { document.body.classList.remove('overflow-hidden') } }"
             @keydown.escape.window="$wire.closeCheckout()">
            <div class="max-h-[94vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-2xl sm:max-w-2xl sm:rounded-3xl" role="dialog" aria-modal="true" aria-labelledby="checkout-title">
                <div class="sticky top-0 z-10 flex items-start justify-between border-b border-gray-100 bg-white/95 px-6 py-4 backdrop-blur">
                    <div>
                        <h2 id="checkout-title" class="display text-3xl text-gray-900">Complete your booking</h2>
                        <p class="text-sm text-gray-500">{{ $service->name }} · {{ $startsAt->format('D d M') }}, {{ $startsAt->format('h:i A') }} – {{ $endsAt->format('h:i A') }} · <strong class="text-gray-900">{{ lkr($quote['total']) }}</strong></p>
                    </div>
                    <button type="button" wire:click="closeCheckout" class="text-gray-400 hover:text-gray-700" aria-label="Close"><i class="fa-solid fa-xmark text-xl"></i></button>
                </div>

                <form wire:submit="placeBooking" class="space-y-6 px-6 py-5">
                    {{-- Details --}}
                    <div>
                        <h3 class="font-semibold text-gray-900">Your details</h3>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="block text-sm">
                                <span class="font-medium text-gray-700">Full name</span>
                                <input wire:model="customerName" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">
                                @error('customerName')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                            <label class="block text-sm">
                                <span class="font-medium text-gray-700">Mobile number</span>
                                <input wire:model="customerPhone" placeholder="07XXXXXXXX" inputmode="numeric" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">
                                @error('customerPhone')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                            <label class="block text-sm sm:col-span-2">
                                <span class="font-medium text-gray-700">Notes for the venue <span class="text-gray-400">(optional)</span></span>
                                <textarea wire:model="notes" rows="2" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5" placeholder="e.g. we'll need bibs for 10 players"></textarea>
                                @error('notes')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                        </div>
                        @error('players')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Payment methods --}}
                    <div>
                        <h3 class="font-semibold text-gray-900">How would you like to pay?</h3>
                        <div class="mt-3 space-y-2.5">
                            @foreach($methods as $m)
                                @php($on = $m->isAvailable())
                                <button type="button" @if($on) wire:click="selectPaymentMethod('{{ $m->value }}')" @else disabled @endif
                                        class="pay-option w-full text-left {{ $on ? '' : 'is-disabled' }} {{ $paymentMethod === $m->value ? 'is-selected' : '' }}">
                                    <span class="pay-icon"><i class="{{ $m->icon() }}"></i></span>
                                    <span class="flex-1">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="font-semibold text-gray-900">{{ $m->label() }}</span>
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $on ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">{{ $m->availabilityLabel() }}</span>
                                            <span class="ml-auto text-[11px] text-gray-400">{{ $m->priority() === 1 ? 'Slot may be replaced by a paid booking' : 'Confirms your slot' }}</span>
                                        </span>
                                        <span class="mt-1 block text-xs text-gray-500">{{ $m->description() }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        @error('paymentMethod')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Bank transfer extras --}}
                    @if($paymentMethod === \App\Enums\PaymentMethod::BankTransfer->value)
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                            <p class="font-semibold"><i class="fa-solid fa-building-columns mr-1"></i>Transfer {{ lkr($quote['total']) }} to:</p>
                            @if($service->venue->hasBankDetails())
                                <p class="mt-1">{{ $service->venue->bank_name }} — {{ $service->venue->bank_branch }}<br>{{ $service->venue->bank_account_name }}<br><span class="font-mono text-base">A/C {{ $service->venue->bank_account_number }}</span></p>
                            @else
                                <p class="mt-1">The venue hasn't published bank details yet — call {{ $service->venue->phone }} to get them.</p>
                            @endif
                            <p class="mt-3 rounded-xl bg-white/70 p-3 text-xs text-blue-900"><i class="fa-regular fa-clock mr-1"></i><strong>{{ $holdMinutes }}-minute hold.</strong> Your slot is reserved as soon as you place the booking. The venue must verify your transfer within {{ $holdMinutes }} minutes or the slot is released again — so transfer and upload your slip right away. You can also upload it on the next page.</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <label class="block">
                                    <span class="font-medium">Bank reference / transaction ID</span>
                                    <input wire:model="bankReference" class="mt-1 w-full rounded-xl border border-blue-200 bg-white px-3 py-2" placeholder="e.g. TXN123456">
                                    @error('bankReference')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                                </label>
                                <div class="block">
                                    <span class="font-medium">Transfer slip <span class="font-normal text-blue-700">(jpg/png/pdf, optional now)</span></span>
                                    <x-file-drop wire:model="proof" accept=".jpg,.jpeg,.png,.pdf" :max-size="5" hint="JPG, PNG or PDF · up to 5 MB" />
                                    <span wire:loading wire:target="proof" class="text-xs text-blue-700"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Uploading…</span>
                                    @if($proof && ! $errors->has('proof'))<span class="text-xs text-emerald-700"><i class="fa-solid fa-check mr-1"></i>{{ $proof->getClientOriginalName() }}</span>@endif
                                    @error('proof')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                    @elseif($paymentMethod === \App\Enums\PaymentMethod::PayAtVenue->value)
                        <p class="rounded-2xl bg-gray-50 p-4 text-xs text-gray-600"><i class="fa-solid fa-circle-info mr-1"></i>Pay-at-venue holds the slot but a customer who pays (bank transfer or, later, online) for the same time can replace it. The venue may call you to confirm; once they do, the slot is locked.</p>
                    @endif

                    <div class="flex flex-col gap-2 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-gray-400">By booking you agree to the venue's cancellation rules.</p>
                        <button type="submit" wire:loading.attr="disabled" wire:target="placeBooking,proof" class="btn-brand py-3.5 text-base" @disabled(! $paymentMethod)>
                            <span wire:loading.remove wire:target="placeBooking">{{ $paymentMethod ? 'Place booking · '.lkr($quote['total']) : 'Select a payment method' }}</span>
                            <span wire:loading wire:target="placeBooking"><i class="fa-solid fa-spinner fa-spin"></i> Placing booking…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
