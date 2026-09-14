@extends('layouts.app')
@section('title', 'Booking '.$booking->reference.' · EntryPoint.lk')

@section('content')
<section class="mx-auto max-w-4xl px-4 pb-16">
    @php($isNew = session('message') && str_contains(session('message'), 'placed'))

    <div class="rounded-3xl {{ $booking->isActive() ? 'bg-gray-900' : 'bg-gray-600' }} p-8 text-white">
        <p class="text-sm uppercase tracking-[.3em] text-red-400">{{ $isNew ? 'Booking placed' : 'Booking' }}</p>
        <h1 class="display mt-1 text-5xl md:text-6xl">{{ $booking->reference }}</h1>
        <div class="mt-4 flex flex-wrap gap-2">
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $booking->status->badge() }}">{{ $booking->status->label() }}</span>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $booking->payment_status->badge() }}">{{ $booking->payment_status->label() }}</span>
            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">{{ $booking->payment_method->label() }}</span>
            @if($booking->isLocked())
                <span class="rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-200"><i class="fa-solid fa-lock mr-1"></i>Slot locked</span>
            @endif
        </div>
        <p class="mt-4 text-gray-300">Show this reference at <strong class="text-white">{{ $booking->venue->name }}</strong> when you arrive.</p>
    </div>

    <div class="mt-8 grid gap-8 md:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Booking details</h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-gray-500">Venue</dt><dd class="font-medium"><a href="{{ route('venues.show', $booking->venue) }}" class="hover:text-brand">{{ $booking->venue->name }}</a></dd><dd class="text-gray-500">{{ $booking->venue->address }}, {{ $booking->venue->city }}</dd></div>
                    <div><dt class="text-gray-500">Service</dt><dd class="font-medium">{{ $booking->service->name }} · {{ $booking->option->name }}</dd>@if($booking->game)<dd class="text-gray-500"><i class="fa-solid fa-gamepad mr-1"></i>{{ $booking->game->name }}</dd>@endif</div>
                    <div><dt class="text-gray-500">When</dt><dd class="font-medium">{{ $booking->starts_at->format('l, d M Y') }}</dd><dd class="text-gray-500">{{ $booking->starts_at->format('h:i A') }} – {{ $booking->ends_at->format('h:i A') }} ({{ $booking->durationLabel() }})</dd></div>
                    <div><dt class="text-gray-500">Booked for</dt><dd class="font-medium">{{ $booking->customer_name }}</dd><dd class="text-gray-500">{{ $booking->customer_phone }}@if($booking->players) · {{ $booking->players }} players @endif</dd></div>
                    @if($booking->notes)<div class="sm:col-span-2"><dt class="text-gray-500">Notes</dt><dd>{{ $booking->notes }}</dd></div>@endif
                </dl>
                <div class="mt-4 space-y-1 border-t border-gray-100 pt-3 text-sm">
                    @foreach($booking->price_breakdown ?? [] as $line)
                        <div class="flex justify-between text-gray-600"><span>{{ $line['label'] }}</span><span>{{ lkr($line['amount']) }}</span></div>
                    @endforeach
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-bold text-gray-900"><span>Total</span><span>{{ lkr($booking->total) }}</span></div>
                </div>
            </div>

            {{-- What happens next --}}
            @if($booking->isActive())
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">What happens next</h2>
                    @if($booking->payment_method === \App\Enums\PaymentMethod::PayAtVenue)
                        <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-600">
                            <li>Your slot is on hold. The venue may call you to confirm — once they confirm, the slot is locked.</li>
                            <li>Until then, a customer who pays online or by bank transfer for the same slot can replace this hold. You'll be notified immediately if that happens.</li>
                            <li>Arrive a few minutes early, quote <strong>{{ $booking->reference }}</strong> and pay <strong>{{ lkr($booking->total) }}</strong> at the counter.</li>
                        </ol>
                    @elseif($booking->payment_method === \App\Enums\PaymentMethod::BankTransfer)
                        @if($booking->isAwaitingVerification())
                            @php($left = $booking->holdMinutesLeft())
                            <div class="mt-3 flex items-center gap-3 rounded-xl {{ $left <= 5 ? 'bg-rose-50 text-rose-800' : 'bg-amber-50 text-amber-800' }} p-3 text-sm" x-data="{ end: {{ $booking->hold_expires_at->getTimestampMs() }}, label: '' , tick() { const s = Math.max(0, Math.round((this.end - Date.now()) / 1000)); this.label = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); if (s === 0) setTimeout(() => location.reload(), 1500); } }" x-init="tick(); setInterval(() => tick(), 1000)">
                                <i class="fa-regular fa-clock text-lg"></i>
                                <div><strong>Slot held for <span x-text="label">{{ $left }} min</span></strong> — the venue has until {{ $booking->hold_expires_at->format('h:i A') }} to verify your transfer, otherwise this booking expires and the slot is released. Transfer and upload your slip now.</div>
                            </div>
                        @endif
                        <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-600">
                            <li>Transfer <strong>{{ lkr($booking->total) }}</strong> to the account below and use <strong>{{ $booking->reference }}</strong> as the remark.</li>
                            <li>Upload your slip here. The venue verifies it and your booking becomes <strong>Confirmed &amp; locked</strong>.</li>
                        </ol>
                        <div class="mt-4 rounded-xl bg-blue-50 p-4 text-sm text-blue-900">
                            @if($booking->venue->hasBankDetails())
                                <p class="font-semibold">{{ $booking->venue->bank_name }} — {{ $booking->venue->bank_branch }}</p>
                                <p>{{ $booking->venue->bank_account_name }}</p>
                                <p class="font-mono text-base">A/C {{ $booking->venue->bank_account_number }}</p>
                            @else
                                <p>The venue hasn't published bank details yet — call {{ $booking->venue->phone }} to get them.</p>
                            @endif
                        </div>
                        @php($payment = $booking->payments->last())
                        @if($payment?->proof_path && $booking->payment_status !== \App\Enums\PaymentStatus::Paid)
                            <p class="mt-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800"><i class="fa-solid fa-check mr-1"></i>Slip uploaded {{ $payment->updated_at->diffForHumans() }}@if($payment->reference) (ref {{ $payment->reference }})@endif. Waiting for the venue to verify.</p>
                        @endif
                        @if($booking->payment_status !== \App\Enums\PaymentStatus::Paid)
                            <form method="POST" action="{{ route('bookings.proof', $booking) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                                @csrf
                                <label class="block text-sm"><span class="text-gray-700">Transfer slip (jpg/png/pdf)</span><input type="file" name="proof" required accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm"></label>
                                <label class="block text-sm"><span class="text-gray-700">Bank reference <span class="text-gray-400">(optional)</span></span><input name="reference" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2"></label>
                                <button class="btn-brand">{{ $payment?->proof_path ? 'Re-upload' : 'Upload slip' }}</button>
                            </form>
                        @endif
                    @endif
                </div>
            @elseif($booking->status === \App\Enums\BookingStatus::Expired)
                <div class="rounded-2xl bg-gray-100 p-6 text-sm text-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900">This booking expired</h2>
                    <p class="mt-2">The bank transfer wasn't verified within {{ setting('payments.bank_transfer_hold_minutes') }} minutes, so the slot was released for other customers. If you did transfer the money, contact the venue on {{ $booking->venue->phone }} quoting {{ $booking->reference }} — they can refund or re-book you.</p>
                    <a href="{{ route('booking.build', $booking->service) }}" class="btn-brand mt-4">Book again</a>
                </div>
            @elseif($booking->status === \App\Enums\BookingStatus::Bumped)
                <div class="rounded-2xl bg-rose-50 p-6 text-sm text-rose-800">
                    <h2 class="text-lg font-semibold">This booking was replaced</h2>
                    <p class="mt-2">A customer paid for the same slot, which outranks a pay-at-venue hold. Next time choose bank transfer (or online payment when available) to lock your slot.</p>
                    <a href="{{ route('booking.build', $booking->service) }}" class="btn-brand mt-4">Book another time</a>
                </div>
            @endif
        </div>

        <aside class="space-y-4 md:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm text-sm">
                <h3 class="font-semibold text-gray-900">Need help?</h3>
                <p class="mt-2 text-gray-600">Call the venue directly:</p>
                <a href="tel:{{ $booking->venue->phone }}" class="btn-ghost mt-3 w-full"><i class="fa-solid fa-phone"></i>{{ $booking->venue->phone }}</a>
            </div>
            @if($booking->isCancellable())
                <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking?')" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    @csrf
                    <h3 class="font-semibold text-gray-900">Can't make it?</h3>
                    <input name="reason" placeholder="Reason (optional)" class="mt-3 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                    <button class="mt-3 w-full rounded-xl border border-rose-200 bg-rose-50 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">Cancel booking</button>
                </form>
            @endif
            <a href="{{ route('bookings.index') }}" class="block text-center text-sm text-gray-500 hover:text-brand">← All my bookings</a>
        </aside>
    </div>
</section>
@endsection
