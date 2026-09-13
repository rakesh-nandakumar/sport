@extends('layouts.app')
@section('title', 'Checkout · Sportee')

@section('content')
<section class="mx-auto max-w-5xl px-4 pb-16" x-data="checkout()">
    <h1 class="display text-4xl md:text-5xl text-gray-900">Checkout</h1>
    <p class="text-gray-500">Review your plan, add your details, then choose how you'd like to pay.</p>

    <form method="POST" action="{{ route('checkout.store') }}" x-ref="form" class="mt-8 grid gap-8 md:grid-cols-[1fr_360px]">
        @csrf
        <input type="hidden" name="payment_method" :value="method">

        {{-- Details --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Your details</h2>
                <p class="text-sm text-gray-500">The venue will use these to reach you about the booking.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">
                        <span class="font-medium text-gray-700">Full name</span>
                        <input name="customer_name" value="{{ old('customer_name', auth()->user()->name) }}" required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">
                        @error('customer_name')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm">
                        <span class="font-medium text-gray-700">Mobile number</span>
                        <input name="customer_phone" value="{{ old('customer_phone', auth()->user()->phone) }}" placeholder="07XXXXXXXX" required inputmode="numeric" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">
                        @error('customer_phone')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                    </label>
                    @if($service->max_players)
                        <label class="block text-sm">
                            <span class="font-medium text-gray-700">Players <span class="text-gray-400">(optional)</span></span>
                            <input name="players" type="number" min="1" max="{{ $service->max_players }}" value="{{ old('players', $players) }}" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5">
                        </label>
                    @endif
                    <label class="block text-sm sm:col-span-2">
                        <span class="font-medium text-gray-700">Notes for the venue <span class="text-gray-400">(optional)</span></span>
                        <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5" placeholder="e.g. we'll need 2 extra controllers / bibs for 10 players">{{ old('notes') }}</textarea>
                    </label>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">How slot priority works</h2>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    <li class="flex gap-2"><i class="fa-solid fa-store mt-1 text-gray-400"></i><span><strong>Pay at venue</strong> holds the slot but has the lowest priority. Only one pay-at-venue hold can exist for a slot.</span></li>
                    <li class="flex gap-2"><i class="fa-solid fa-building-columns mt-1 text-gray-400"></i><span><strong>Bank transfer</strong> outranks a pay-at-venue hold and is locked once the venue verifies your slip.</span></li>
                    <li class="flex gap-2"><i class="fa-regular fa-credit-card mt-1 text-gray-400"></i><span><strong>Online payments</strong> (coming soon) confirm instantly and outrank everything else.</span></li>
                </ul>
            </div>

            @error('payment_method')
                <p class="rounded-xl bg-rose-50 p-4 text-sm text-rose-700">{{ $message }}</p>
            @enderror

            <button type="button" @click="open = true" class="btn-brand w-full py-4 text-base md:hidden">Confirm & choose payment</button>
        </div>

        {{-- Summary --}}
        <aside class="space-y-4 md:sticky md:top-28 md:self-start">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <img src="{{ $service->imageUrl() }}" alt="" class="h-32 w-full object-cover">
                <div class="p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: {{ $service->activityType->color }}">{{ $service->activityType->name }}</p>
                    <h3 class="font-semibold text-gray-900">{{ $service->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $venue->name }} · {{ $venue->city }}</p>
                    <dl class="mt-4 space-y-1.5 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Type</dt><dd class="font-medium">{{ $option->name }}</dd></div>
                        @if($game)<div class="flex justify-between"><dt class="text-gray-500">Game</dt><dd class="font-medium">{{ $game->name }}</dd></div>@endif
                        <div class="flex justify-between"><dt class="text-gray-500">When</dt><dd class="font-medium text-right">{{ $start->format('D, d M Y') }}<br>{{ $start->format('h:i A') }} – {{ $end->format('h:i A') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Duration</dt><dd class="font-medium">{{ minutes_label($slots * $service->slot_minutes) }}</dd></div>
                    </dl>
                    <div class="mt-4 space-y-1 border-t border-gray-100 pt-3 text-sm">
                        @foreach($quote['lines'] as $line)
                            <div class="flex justify-between text-gray-600"><span>{{ $line['label'] }}</span><span>{{ lkr($line['amount']) }}</span></div>
                        @endforeach
                        <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-bold text-gray-900"><span>Total</span><span>{{ lkr($quote['total']) }}</span></div>
                    </div>
                    <button type="button" @click="open = true" class="btn-brand mt-5 hidden w-full md:flex">Confirm & choose payment</button>
                    <a href="{{ route('booking.build', $service) }}" class="mt-2 block text-center text-xs text-gray-400 hover:text-brand">← Change plan</a>
                </div>
            </div>
        </aside>

        {{-- Payment modal --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-[100000] flex items-end justify-center bg-black/60 p-0 sm:items-center sm:p-4" @keydown.escape.window="open = false">
            <div @click.outside="open = false" x-show="open" x-transition
                 class="max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-white p-6 shadow-2xl sm:max-w-lg sm:rounded-3xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="display text-3xl text-gray-900">Choose payment</h2>
                        <p class="text-sm text-gray-500">Total due: <strong class="text-gray-900">{{ lkr($quote['total']) }}</strong></p>
                    </div>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-700"><i class="fa-solid fa-xmark text-xl"></i></button>
                </div>

                <div class="mt-5 space-y-3">
                    @foreach($methods as $m)
                        <label class="pay-option {{ $m->isAvailable() ? '' : 'is-disabled' }}"
                               :class="{ 'is-selected': method === '{{ $m->value }}' }"
                               @if($m->isAvailable()) @click="method = '{{ $m->value }}'" @endif>
                            <span class="pay-icon"><i class="{{ $m->icon() }}"></i></span>
                            <span class="flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-900">{{ $m->label() }}</span>
                                    @if($m->isAvailable())
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Available</span>
                                    @else
                                        <span class="rounded-full bg-gray-200 px-2 py-0.5 text-[11px] font-semibold text-gray-600">Coming soon</span>
                                    @endif
                                    <span class="ml-auto text-[11px] text-gray-400">priority {{ $m->priority() }}/3</span>
                                </span>
                                <span class="mt-1 block text-xs text-gray-500">{{ $m->description() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <div x-show="method === 'bank_transfer'" x-cloak class="mt-4 rounded-xl bg-blue-50 p-4 text-sm text-blue-900">
                    @if($venue->hasBankDetails())
                        <p class="font-semibold">Transfer to:</p>
                        <p>{{ $venue->bank_name }} — {{ $venue->bank_branch }}<br>{{ $venue->bank_account_name }}<br>A/C {{ $venue->bank_account_number }}</p>
                        <p class="mt-2 text-xs">You'll be able to upload the slip on the confirmation page.</p>
                    @else
                        <p>The venue will share bank details on your confirmation page. Upload the slip there once you've transferred.</p>
                    @endif
                </div>

                <button type="submit" :disabled="!method" class="btn-brand mt-6 w-full py-3.5 text-base">
                    <span x-text="method ? 'Place booking' : 'Select a payment method'"></span>
                </button>
                <p class="mt-2 text-center text-xs text-gray-400">By booking you agree to the venue's cancellation rules.</p>
            </div>
        </div>
    </form>
</section>
@endsection

@push('head')
<script>
    function checkout() {
        return { open: false, method: @json(old('payment_method', '')) };
    }
</script>
<style>[x-cloak]{display:none!important}</style>
@endpush
