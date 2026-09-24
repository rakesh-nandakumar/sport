<x-filament-panels::page>
    @if(! $booking)
        <div class="grid gap-6 md:grid-cols-2">
            <x-filament::section icon="heroicon-o-camera" heading="QR scanner (paid bookings)">
                <div
                    x-data="{
                        started: false,
                        error: false,
                        start() {
                            if (typeof Html5Qrcode === 'undefined') { this.error = true; return }
                            const scanner = new Html5Qrcode('qr-reader');
                            let stopped = false;
                            scanner.start(
                                { facingMode: 'environment' },
                                { fps: 10, qrbox: { width: 250, height: 250 } },
                                (decodedText) => {
                                    if (stopped) return;
                                    stopped = true;
                                    scanner.stop().catch(() => {}).finally(() => $wire.scan(decodedText));
                                },
                            ).then(() => this.started = true).catch(() => this.error = true);
                        },
                    }"
                    x-init="start()"
                >
                    <div id="qr-reader" style="width:100%"></div>
                    <p x-show="error" x-cloak class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        Camera scanning isn't available on this device/browser (no camera, or permission was denied). Use manual entry instead.
                    </p>
                </div>
            </x-filament::section>

            <x-filament::section icon="heroicon-o-pencil" heading="Manual entry">
                <form wire:submit="lookup" class="flex gap-2">
                    <div class="flex-1">
                        <input
                            type="text"
                            wire:model="code"
                            placeholder="EPT-XXXXXX"
                            autofocus
                            class="fi-input block w-full rounded-lg border-gray-300 py-1.5 text-sm shadow-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        />
                    </div>
                    <x-filament::button type="submit">Find booking</x-filament::button>
                </form>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Type the booking reference, or the code from the QR. Pay at Venue bookings have no QR: look them up by reference and verify their NIC.</p>
            </x-filament::section>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2">
            <x-filament::section heading="Booking details">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-lg font-bold">{{ $booking->reference }}</span>
                        <x-filament::badge :color="match(true) { $booking->isCheckedIn() => 'success', $booking->isActive() => 'warning', default => 'gray' }">
                            {{ $booking->status->label() }}
                        </x-filament::badge>
                        <span class="ms-auto text-lg font-bold">{{ lkr($booking->total) }}</span>
                    </div>
                    <dl class="grid grid-cols-3 gap-y-1 text-sm">
                        <dt class="text-gray-500 dark:text-gray-400">Service</dt>
                        <dd class="col-span-2">{{ $booking->service->name }} · {{ $booking->option->name }}@if($booking->game) · 🎮 {{ $booking->game->name }}@endif</dd>
                        <dt class="text-gray-500 dark:text-gray-400">Venue</dt>
                        <dd class="col-span-2">{{ $booking->venue->name }}</dd>
                        <dt class="text-gray-500 dark:text-gray-400">When</dt>
                        <dd class="col-span-2">{{ $booking->timeRangeLabel() }}</dd>
                        <dt class="text-gray-500 dark:text-gray-400">Customer</dt>
                        <dd class="col-span-2">{{ $booking->customer_name }} · <a href="tel:{{ $booking->customer_phone }}" class="underline">{{ $booking->customer_phone }}</a></dd>
                    </dl>
                </div>
            </x-filament::section>

            <x-filament::section heading="Check-in">
                <div class="space-y-3">
                    @if($booking->payment_method === \App\Enums\PaymentMethod::PayAtVenue)
                        <div class="rounded-lg bg-warning-50 p-3 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
                            <p class="font-semibold">NIC verification required — no QR for Pay at Venue</p>
                            <p class="mt-1">Match the customer's physical NIC to both submitted images before confirming check-in.</p>
                            <div class="mt-2 flex gap-3">
                                @if($booking->nic_front_path)<a class="underline" target="_blank" href="{{ route('bookings.identity.show', [$booking, 'front']) }}">View front</a>@endif
                                @if($booking->nic_back_path)<a class="underline" target="_blank" href="{{ route('bookings.identity.show', [$booking, 'back']) }}">View back</a>@endif
                            </div>
                        </div>
                    @endif
                    @if($booking->isCheckedIn())
                        <div class="rounded-lg bg-success-50 p-3 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
                            Checked in at {{ $booking->checked_in_at->format('h:i A') }} by {{ $booking->checkedInBy?->name }}
                        </div>
                    @elseif($booking->isActive())
                        <x-filament::button wire:click="checkIn" color="success" icon="heroicon-o-check" class="w-full">
                            Confirm check-in
                        </x-filament::button>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">This booking is {{ Str::lower($booking->status->label()) }} and can't be checked in.</p>
                    @endif

                    <x-filament::button wire:click="scanAnother" color="gray" outlined icon="heroicon-o-qr-code" class="w-full">
                        Find another booking
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>
    @endif

    @once
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" integrity="sha384-c9d8RFSL+u3exBOJ4Yp3HUJXS4znl9f+z66d1y54ig+ea249SpqR+w1wyvXz/lk+" crossorigin="anonymous"></script>
    @endonce
</x-filament-panels::page>
