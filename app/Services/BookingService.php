<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\Game;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceOption;
use App\Models\User;
use App\Notifications\BookingNotification;
use App\Notifications\CriticalNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        protected AvailabilityService $availability,
        protected PricingService $pricing,
    ) {}

    /**
     * Reserve a slot. Capacity is checked block by block, so identical units are treated as
     * interchangeable (a 10–12 booking fits between a 10–11 and an 11–12 booking on a 2-unit option).
     * Competing bookings with a lower payment priority are replaced ("bumped"); equal or higher
     * priority bookings block the request.
     *
     * @throws SlotUnavailableException
     */
    public function reserve(
        User $customer,
        Service $service,
        ServiceOption $option,
        CarbonInterface $start,
        int $slots,
        PaymentMethod $method,
        array $details,
        Game|Collection|array|null $games = null,
    ): Booking {
        return $this->reserveMany($customer, [[
            'service' => $service,
            'option' => $option,
            'start' => $start,
            'slots' => $slots,
            'games' => $games instanceof Game ? [$games] : $games,
        ]], $method, $details)->first();
    }

    /**
     * Reserves several activities at one venue as one order. Every item is checked against its own
     * service capacity inside the same transaction: if one item cannot fit, none of the order is
     * created or charged.
     *
     * @param  array<int, array{service: Service, option: ServiceOption, start: CarbonInterface, slots: int, games?: iterable<Game|int>|null}>  $items
     * @return Collection<int, Booking>
     *
     * @throws SlotUnavailableException
     */
    public function reserveMany(User $customer, array $items, PaymentMethod $method, array $details): Collection
    {
        if (! $method->isAvailable()) {
            throw new SlotUnavailableException($method->label().' is not available right now. Please choose another payment method.');
        }
        if ($method === PaymentMethod::PayAtVenue && $customer->fresh()->isPayAtVenueBanned()) {
            throw new SlotUnavailableException('Pay at Venue is unavailable for this account. Please contact support if you believe this is a mistake.');
        }
        $this->expireStaleHolds();
        $prepared = $this->prepareOrderItems($items);
        $paymentStatus = $method === PaymentMethod::BankTransfer ? PaymentStatus::PendingVerification : PaymentStatus::Unpaid;
        $priority = Booking::priorityFor($method, $paymentStatus);
        $holdExpiresAt = $method === PaymentMethod::BankTransfer
            ? now()->addMinutes((int) setting('payments.bank_transfer_hold_minutes'))
            : null;
        $total = $prepared->sum(fn (array $item) => (float) $item['quote']['total']);
        $venueId = $prepared->first()['service']->venue_id;

        $result = DB::transaction(function () use ($customer, $prepared, $method, $details, $paymentStatus, $priority, $holdExpiresAt, $total, $venueId) {
            $order = BookingOrder::create([
                'user_id' => $customer->id,
                'venue_id' => $venueId,
                'payment_method' => $method,
                'payment_status' => $paymentStatus,
                'total' => $total,
                'hold_expires_at' => $holdExpiresAt,
            ]);

            $bookings = collect();
            $toBump = collect();

            foreach ($prepared as $item) {
                /** @var Service $service */
                $service = $item['service'];
                /** @var ServiceOption $option */
                $option = $item['option'];
                /** @var CarbonInterface $start */
                $start = $item['start'];
                /** @var CarbonInterface $end */
                $end = $item['end'];
                $slots = $item['slots'];
                /** @var Collection<int, Game> $games */
                $games = $item['games'];
                $quote = $item['quote'];

                $competing = $this->availability->competingBookings($service, $option, $start, $end, lock: true);
                $toBump = $toBump->concat($this->resolveCapacity($service, $option, $start, $slots, $priority, $competing));

                $booking = Booking::create([
                    'booking_order_id' => $order->id,
                    'user_id' => $customer->id,
                    'venue_id' => $service->venue_id,
                    'service_id' => $service->id,
                    'service_option_id' => $option->id,
                    // Retained for legacy callers and reports; the pivot stores every selected title.
                    'game_id' => $games->first()?->id,
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'slots' => $slots,
                    'players' => $item['players'] ?? $details['players'] ?? null,
                    'unit_price' => $quote['unit_price'],
                    'subtotal' => $quote['subtotal'],
                    'discount' => $quote['discount'],
                    'total' => $quote['total'],
                    'price_breakdown' => $quote['lines'],
                    'status' => BookingStatus::Pending,
                    'payment_method' => $method,
                    'payment_status' => $paymentStatus,
                    'priority' => $priority,
                    'hold_expires_at' => $holdExpiresAt,
                    'customer_name' => $details['customer_name'],
                    'customer_phone' => $details['customer_phone'],
                    'notes' => $details['notes'] ?? null,
                    'nic_front_path' => $method === PaymentMethod::PayAtVenue ? ($details['nic_front_path'] ?? $customer->nic_front_path) : null,
                    'nic_back_path' => $method === PaymentMethod::PayAtVenue ? ($details['nic_back_path'] ?? $customer->nic_back_path) : null,
                ]);
                $booking->games()->sync($games->pluck('id')->all());
                $bookings->push($booking);
            }

            $primary = $bookings->first();
            if ($method === PaymentMethod::BankTransfer) {
                Payment::create([
                    'booking_id' => $primary->id,
                    'booking_order_id' => $order->id,
                    'method' => $method,
                    'amount' => $order->total,
                    'status' => PaymentStatus::PendingVerification,
                    'reference' => $details['bank_reference'] ?? null,
                ]);
            }

            $bumped = $toBump->unique('id')->values();
            foreach ($bumped as $victim) {
                $this->bump($victim, $primary);
            }

            $bookings->each(fn (Booking $item) => $item->load(['venue.owner', 'service', 'option', 'game', 'games']));

            return compact('bookings', 'bumped', 'order');
        });

        // The reservation transaction has committed. Sending the notifications here prevents an
        // email from describing a booking that was subsequently rolled back.
        foreach ($result['bumped'] as $victim) {
            $this->notifyBumped($victim, $result['bookings']->first());
        }
        $this->notifyCreated($result['bookings']->first(), $result['bookings'], $result['order']);

        return $result['bookings'];
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function prepareOrderItems(array $items): Collection
    {
        if ($items === []) {
            throw new SlotUnavailableException('Add at least one activity before checking out.');
        }

        $prepared = collect($items)->map(function ($item) {
            $service = $item['service'] ?? null;
            $option = $item['option'] ?? null;
            $start = $item['start'] ?? null;
            $slots = (int) ($item['slots'] ?? 0);

            if (! $service instanceof Service || ! $option instanceof ServiceOption || ! $start instanceof CarbonInterface) {
                throw new SlotUnavailableException('One of the activities in this order is no longer valid. Please add it again.');
            }

            $service->loadMissing(['venue.owner', 'activityType', 'games']);
            if ($option->service_id !== $service->id) {
                throw new SlotUnavailableException('That option does not belong to this service.');
            }
            if (! $service->is_active || ! $service->venue->isLive()) {
                throw new SlotUnavailableException("{$service->name} is not taking bookings at the moment.");
            }

            $this->assertWithinRules($service, $start, $slots);
            $gameIds = collect($item['games'] ?? [])
                ->map(fn ($game) => $game instanceof Game ? $game->id : (int) $game)
                ->filter()
                ->unique()
                ->values();
            $games = $gameIds->isEmpty()
                ? collect()
                : $service->games()->whereKey($gameIds)->get();

            if ($games->count() !== $gameIds->count()) {
                throw new SlotUnavailableException('Choose games offered by this service.');
            }
            if ($service->requiresGame() && $games->isEmpty()) {
                throw new SlotUnavailableException("Choose at least one game for {$service->name}.");
            }
            if (! $service->requiresGame() && $games->isNotEmpty()) {
                throw new SlotUnavailableException("{$service->name} does not use game selections.");
            }
            if ($games->count() > $service->maxGameSelections($slots)) {
                throw new SlotUnavailableException("Choose no more than {$service->maxGameSelections($slots)} games for this session length.");
            }

            return [
                'service' => $service,
                'option' => $option,
                'start' => $start,
                'end' => $start->copy()->addMinutes($slots * $service->slot_minutes),
                'slots' => $slots,
                'players' => $item['players'] ?? null,
                'games' => $games,
                'quote' => $this->pricing->quote($service, $option, $start, $slots),
            ];
        });

        $venueIds = $prepared->pluck('service.venue_id')->unique();
        if ($venueIds->count() !== 1) {
            throw new SlotUnavailableException('An order can only include activities at one venue.');
        }

        return $prepared;
    }

    /**
     * Check every block of the requested range against the option capacity. Returns the bookings that
     * must be bumped to make room, or throws when equal/higher-priority bookings already fill a block.
     *
     * @throws SlotUnavailableException
     */
    protected function resolveCapacity(Service $service, ServiceOption $option, CarbonInterface $start, int $slots, int $priority, Collection $competing): Collection
    {
        $toBump = collect();

        for ($i = 0; $i < $slots; $i++) {
            $blockStart = $start->copy()->addMinutes($i * $service->slot_minutes);
            $blockEnd = $blockStart->copy()->addMinutes($service->slot_minutes);

            $inBlock = $competing
                ->reject(fn (Booking $b) => $toBump->contains('id', $b->id))
                ->filter(fn (Booking $b) => $this->availability->occupies($b, $blockStart, $blockEnd, $service->buffer_minutes));

            $blocking = $inBlock->filter(fn (Booking $b) => $b->priority >= $priority);
            if ($blocking->count() >= $option->capacity) {
                throw new SlotUnavailableException('That time is no longer available. Please pick another slot.');
            }

            $unitsToFree = $inBlock->count() - $option->capacity + 1;
            if ($unitsToFree > 0) {
                // Bump the weakest holds first; among equals the most recent one loses.
                $victims = $inBlock
                    ->filter(fn (Booking $b) => $b->priority < $priority)
                    ->sortBy([['priority', 'asc'], ['created_at', 'desc']])
                    ->take($unitsToFree);
                $toBump = $toBump->concat($victims);
            }
        }

        return $toBump->unique('id')->values();
    }

    public function cancel(Booking $booking, User $actor, ?string $reason = null): void
    {
        $bookings = $this->relatedBookings($booking);
        $isPayAtVenueFailure = $actor->id === $booking->user_id
            && $bookings->contains(fn (Booking $item) => $item->payment_method === PaymentMethod::PayAtVenue);

        foreach ($bookings->filter->isActive() as $item) {
            $item->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'pay_at_venue_failure_at' => $isPayAtVenueFailure ? now() : null,
            ]);
        }

        $label = "{$booking->service->name} at {$booking->venue->name} on {$booking->starts_at->format('d M, h:i A')}";
        $orderLabel = $bookings->count() > 1 ? 'venue order '.$booking->order?->reference : 'booking '.$booking->reference;

        if ($actor->id === $booking->user_id) {
            $booking->venue->owner->notify(new CriticalNotification(
                ucfirst($orderLabel).' was cancelled by the customer',
                ucfirst($orderLabel)." was cancelled by the customer ({$label}).",
                'danger',
                $booking,
                mailLines: [
                    "{$booking->customer_name} cancelled {$orderLabel}.",
                    "Cancelled session: {$label}.",
                    $reason ? "Reason provided: {$reason}" : 'No cancellation reason was provided.',
                ],
                actionText: 'View booking',
            ));
            $booking->user->notify(new CriticalNotification(
                'Your '.strtolower($orderLabel).' cancellation is confirmed',
                'You cancelled '.strtolower($orderLabel)." ({$label}).",
                'danger',
                $booking,
                mailLines: array_filter([
                    "Your cancellation for {$label} has been recorded.",
                    $reason ? "Reason: {$reason}" : null,
                    $bookings->contains(fn (Booking $item) => $item->payment_status === PaymentStatus::Paid)
                        ? 'This cancellation does not confirm a refund. Please contact the venue about payment arrangements.'
                        : null,
                ]),
                actionText: 'View booking',
            ));
            if ($isPayAtVenueFailure) {
                $this->applyPayAtVenueRestriction($booking->user);
            }
        } else {
            $booking->user->notify(new CriticalNotification(
                'Your '.strtolower($orderLabel).' was cancelled by the venue',
                'The venue cancelled your '.strtolower($orderLabel)." ({$label}).".($reason ? " Reason: {$reason}" : ''),
                'danger',
                $booking,
                mailLines: array_filter([
                    "{$booking->venue->name} cancelled {$orderLabel}: {$label}.",
                    $reason ? "Reason: {$reason}" : 'The venue did not provide a reason.',
                    $bookings->contains(fn (Booking $item) => $item->payment_status === PaymentStatus::Paid)
                        ? 'If you have already paid, please contact the venue about payment arrangements.'
                        : null,
                ]),
                actionText: 'View booking',
            ));
        }
    }

    /** Vendor confirmation locks the slot so it can no longer be replaced or expire. */
    public function vendorConfirm(Booking $booking): void
    {
        $booking->update([
            'status' => BookingStatus::Confirmed,
            'vendor_confirmed_at' => now(),
            'hold_expires_at' => null,
            'priority' => 3,
        ]);

        $booking->user->notify(new CriticalNotification(
            "Booking {$booking->reference} confirmed and locked",
            "{$booking->venue->name} confirmed your booking {$booking->reference}. Your slot is now locked in.",
            'success',
            $booking,
            mailLines: [
                "{$booking->venue->name} confirmed your booking for {$booking->starts_at->format('D d M, h:i A')}.",
                'Your slot is now locked and cannot be replaced by a higher-priority booking.',
            ],
            actionText: 'View booking',
        ));
    }

    public function markPaid(Booking $booking, User $verifier, ?string $reference = null): void
    {
        DB::transaction(function () use ($booking, $verifier, $reference) {
            $bookings = $this->relatedBookings($booking, lock: true);
            $primary = $bookings->sortBy('id')->first();
            $order = $primary->booking_order_id
                ? BookingOrder::whereKey($primary->booking_order_id)->lockForUpdate()->first()
                : null;
            $payment = ($order?->payments()->latest()->first()) ?? $primary->payments()->latest()->first() ?? new Payment([
                'booking_id' => $primary->id,
                'booking_order_id' => $order?->id,
                'method' => $booking->payment_method,
                'amount' => $order?->total ?? $booking->total,
            ]);

            $payment->fill([
                'status' => PaymentStatus::Paid,
                'reference' => $reference ?: $payment->reference,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
            ])->save();

            foreach ($bookings->filter->isActive() as $item) {
                $item->update([
                    'payment_status' => PaymentStatus::Paid,
                    'status' => BookingStatus::Confirmed,
                    'hold_expires_at' => null,
                    'priority' => 3,
                ]);
            }
            $order?->update(['payment_status' => PaymentStatus::Paid, 'hold_expires_at' => null]);
        });

        $booking->refresh()->loadMissing('order.bookings');
        $label = $booking->order?->bookings->count() > 1 ? 'venue order '.$booking->order->reference : 'booking '.$booking->reference;
        $booking->user->notify(new CriticalNotification(
            'Payment verified — '.$label.' is confirmed',
            'Payment for '.$label.' was verified. Your booking is confirmed.',
            'success',
            $booking,
            mailLines: [
                "Payment for {$label} has been recorded by {$booking->venue->name}.",
                'Your booking is confirmed and the slot is locked in.',
            ],
            actionText: 'View booking',
        ));
    }

    public function attachProof(Booking $booking, string $path, ?string $reference): void
    {
        $bookings = $this->relatedBookings($booking);
        $primary = $bookings->sortBy('id')->first();
        $order = $primary->booking_order_id ? $primary->order : null;
        $payment = ($order?->payments()->latest()->first()) ?? $primary->payments()->latest()->first() ?? Payment::create([
            'booking_id' => $primary->id,
            'booking_order_id' => $order?->id,
            'method' => $booking->payment_method,
            'amount' => $order?->total ?? $booking->total,
            'status' => PaymentStatus::PendingVerification,
        ]);

        $payment->update(['proof_path' => $path, 'reference' => $reference ?: $payment->reference]);
        foreach ($bookings->filter->isActive() as $item) {
            $item->update(['payment_status' => PaymentStatus::PendingVerification]);
        }
        $order?->update(['payment_status' => PaymentStatus::PendingVerification]);

        $deadline = $booking->hold_expires_at ? ' Please verify before '.$booking->hold_expires_at->format('h:i A').' or the slot will be released.' : '';
        $label = $bookings->count() > 1 ? 'venue order '.$order?->reference : 'booking '.$booking->reference;
        $booking->venue->owner->notify(new CriticalNotification(
            "Bank transfer slip uploaded for {$label}",
            "A bank transfer slip was uploaded for {$label}.{$deadline}",
            'system',
            $booking,
            mailLines: array_filter([
                "{$booking->customer_name} uploaded a bank transfer slip for {$label}.",
                $booking->hold_expires_at ? 'Verify it before '.$booking->hold_expires_at->format('D d M, h:i A').' to keep the slot reserved.' : null,
                'Check the slip against your bank statement before confirming payment.',
            ]),
            actionText: 'Review payment',
        ));
        $booking->user->notify(new BookingNotification(
            "Your bank transfer slip for {$label} was received and is awaiting the venue's verification.",
            'system',
            $booking,
        ));
    }

    public function complete(Booking $booking, bool $noShow = false): void
    {
        $isPayAtVenueFailure = $noShow && $booking->payment_method === PaymentMethod::PayAtVenue;
        $booking->update([
            'status' => $noShow ? BookingStatus::NoShow : BookingStatus::Completed,
            'pay_at_venue_failure_at' => $isPayAtVenueFailure ? now() : null,
        ]);

        if (! $noShow) {
            $booking->user->notify(new BookingNotification(
                "Your booking {$booking->reference} at {$booking->venue->name} was marked as completed. Thanks for using ".config('app.name').'.',
                'success',
                $booking,
            ));
        } elseif ($isPayAtVenueFailure) {
            $booking->user->notify(new CriticalNotification(
                "Pay at Venue no-show recorded for {$booking->reference}",
                "{$booking->venue->name} recorded you as a no-show for booking {$booking->reference}.",
                'danger',
                $booking,
                mailLines: [
                    "{$booking->venue->name} recorded a no-show for your Pay at Venue booking on {$booking->starts_at->format('D d M, h:i A')}.",
                    'Pay at Venue no-shows count toward the limit that can restrict this payment option on your account.',
                    'Contact the venue or support promptly if this was recorded in error.',
                ],
                actionText: 'View booking',
            ));
            $this->applyPayAtVenueRestriction($booking->user);
        } else {
            $booking->user->notify(new BookingNotification(
                "{$booking->venue->name} recorded a no-show for booking {$booking->reference}. Contact the venue if this was recorded in error.",
                'danger',
                $booking,
            ));
        }
    }

    /**
     * Check a customer in at the venue via their QR code. The actual write is an atomic conditional
     * UPDATE (only when checked_in_at is still null), so two near-simultaneous scans of the same QR
     * can't both succeed even on drivers (SQLite) where lockForUpdate() doesn't take a real row lock.
     *
     * @throws SlotUnavailableException
     */
    public function checkIn(Booking $booking, User $staff): void
    {
        DB::transaction(function () use ($booking, $staff) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

            if ($locked->checked_in_at !== null) {
                $locked->loadMissing('checkedInBy');
                $by = $locked->checkedInBy ? " by {$locked->checkedInBy->name}" : '';
                throw new SlotUnavailableException("This QR code has already been used — checked in at {$locked->checked_in_at->format('h:i A')}{$by}.");
            }

            if (! $locked->isActive()) {
                throw new SlotUnavailableException('This booking is '.Str::lower($locked->status->label()).' and cannot be checked in.');
            }

            $updated = Booking::where('id', $locked->id)
                ->whereNull('checked_in_at')
                ->update(['checked_in_at' => now(), 'checked_in_by' => $staff->id]);

            if ($updated === 0) {
                // Lost the race to a concurrent scan between the read above and this write.
                $locked->refresh()->loadMissing('checkedInBy');
                $by = $locked->checkedInBy ? " by {$locked->checkedInBy->name}" : '';
                throw new SlotUnavailableException("This QR code has already been used — checked in at {$locked->checked_in_at->format('h:i A')}{$by}.");
            }
        });

        $booking->refresh();
        $booking->user->notify(new BookingNotification(
            "You checked in for booking {$booking->reference} at {$booking->venue->name}.",
            'success',
            $booking,
        ));
    }

    /**
     * Release unverified bank-transfer holds whose window has closed. Runs from the scheduler every
     * minute and opportunistically before new reservations, so it works without a cron in local dev.
     *
     * @return int number of bookings expired
     */
    public function expireStaleHolds(): int
    {
        $stale = Booking::holdExpired()->with(['user', 'venue.owner', 'service'])->get();
        $minutes = (int) setting('payments.bank_transfer_hold_minutes');
        $expired = 0;

        foreach ($stale->groupBy(fn (Booking $booking) => $booking->booking_order_id ?: 'booking-'.$booking->id) as $group) {
            /** @var Booking $booking */
            $booking = $group->first();
            $bookings = $this->relatedBookings($booking)->filter->isActive();
            if ($bookings->isEmpty()) {
                continue;
            }

            foreach ($bookings as $item) {
                $item->update([
                    'status' => BookingStatus::Expired,
                    'cancelled_at' => now(),
                    'cancel_reason' => 'Bank transfer was not verified in time',
                ]);
            }
            $order = $booking->booking_order_id ? $booking->order : null;
            if ($order) {
                $order->payments()->where('status', PaymentStatus::PendingVerification)->update(['status' => PaymentStatus::Unpaid]);
                $order->update(['payment_status' => PaymentStatus::Unpaid]);
            } else {
                $booking->payments()->where('status', PaymentStatus::PendingVerification)->update(['status' => PaymentStatus::Unpaid]);
            }

            $when = $booking->starts_at->format('d M, h:i A');
            $booking->user->notify(new CriticalNotification(
                "Bank transfer hold expired for {$booking->reference}",
                "Booking {$booking->reference} ({$booking->service->name}, {$when}) expired because the bank transfer was not verified within {$minutes} minutes. Its QR code is no longer valid and the slot is open again: book it once more and upload your slip straight away, or choose Pay at Venue.",
                'danger',
                $booking,
                mailLines: [
                    "Your bank-transfer hold for {$booking->service->name} on {$when} expired because the venue did not verify it within {$minutes} minutes.",
                    'The slot has been released and the QR code is no longer valid.',
                    'If you sent a transfer, contact the venue with your proof before making another booking.',
                ],
                actionText: 'View booking',
            ));
            $booking->venue->owner->notify(new CriticalNotification(
                "Bank transfer verification deadline missed for {$booking->reference}",
                "Bank transfer hold {$booking->reference} ({$when}) expired unverified and the slot was released.",
                'danger',
                $booking,
                mailLines: [
                    "The verification deadline for bank-transfer booking {$booking->reference} ({$when}) has passed.",
                    'The booking was released and the customer has been informed.',
                ],
                actionText: 'View booking',
            ));
            $expired += $bookings->count();
        }

        return $expired;
    }

    protected function bump(Booking $victim, Booking $winner): void
    {
        $victims = $this->relatedBookings($victim)->filter->isActive();
        if ($victims->isEmpty()) {
            return;
        }
        foreach ($victims as $item) {
            $item->update([
                'status' => BookingStatus::Bumped,
                'bumped_by_booking_id' => $winner->id,
                'cancelled_at' => now(),
                'cancel_reason' => 'Replaced by a higher-priority payment',
            ]);
        }

    }

    protected function notifyBumped(Booking $victim, Booking $winner): void
    {
        $replacement = $winner->payment_method->label();
        $victim->user->notify(new CriticalNotification(
            "Your Pay at Venue booking {$victim->reference} was replaced",
            "Your Pay at Venue booking {$victim->reference} for {$victim->service->name} on {$victim->starts_at->format('d M, h:i A')} was replaced by a higher-priority {$replacement} booking. Its QR code is no longer valid.",
            'danger',
            $victim,
            mailLines: [
                "Your Pay at Venue booking for {$victim->service->name} on {$victim->starts_at->format('D d M, h:i A')} was replaced by a higher-priority {$replacement} booking.",
                'Your QR code is no longer valid and the slot is no longer reserved for you.',
                'Please choose another time, or use a payment method that confirms the slot when available.',
            ],
            actionText: 'View booking',
        ));
    }

    /** @param Collection<int, Booking> $bookings */
    protected function notifyCreated(Booking $booking, Collection $bookings, BookingOrder $order): void
    {
        $when = $booking->starts_at->format('D d M, h:i A');
        $activities = $bookings->map(fn (Booking $item) => $item->service->name)->unique()->join(', ');
        $label = $bookings->count() > 1 ? "Venue order {$order->reference}" : "Booking {$booking->reference}";

        $next = $booking->payment_method === PaymentMethod::BankTransfer
            ? 'The venue has until '.$booking->hold_expires_at->format('h:i A').' to verify your transfer slip.'
            : 'Show the QR code when you arrive and pay at the venue.';

        $booking->user->notify(new CriticalNotification(
            "{$label} placed at {$booking->venue->name}",
            "{$label} placed: {$activities} at {$booking->venue->name}, {$when}. {$next}",
            'success',
            $booking,
            mailLines: [
                "{$label} for {$activities} at {$booking->venue->name} has been placed for {$when}.",
                $booking->payment_method === PaymentMethod::BankTransfer
                    ? 'Your slot is held until '.$booking->hold_expires_at->format('h:i A').'. Upload a bank-transfer slip and wait for the venue to verify it.'
                    : 'Pay at the venue when you arrive. This reservation remains provisional until the venue locks it, so keep an eye on your notifications.',
            ],
            actionText: 'View booking',
        ));

        $booking->venue->owner->notify(new CriticalNotification(
            "New {$booking->payment_method->label()} booking {$booking->reference}",
            "New booking {$booking->reference}: {$booking->customer_name} booked {$booking->service->name} ({$booking->option->name}) for {$when} · {$booking->payment_method->label()}.".
            ($booking->hold_expires_at ? ' Verify the transfer before '.$booking->hold_expires_at->format('h:i A').'.' : ''),
            'success',
            $booking,
            mailLines: array_filter([
                "{$booking->customer_name} placed {$label} for {$booking->service->name} ({$booking->option->name}) on {$when}.",
                "Payment method: {$booking->payment_method->label()}.",
                $booking->hold_expires_at ? 'Verify the bank transfer before '.$booking->hold_expires_at->format('D d M, h:i A').' or the slot will be released.' : 'Review the booking and lock the slot after confirming arrangements with the customer.',
            ]),
            actionText: 'View booking',
        ));
    }

    /** @return Collection<int, Booking> */
    protected function relatedBookings(Booking $booking, bool $lock = false): Collection
    {
        if (! $booking->booking_order_id) {
            return collect([$booking]);
        }

        $query = Booking::where('booking_order_id', $booking->booking_order_id)->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    /** Restrict repeated pay-at-venue failures until a Super Administrator lifts the restriction. */
    protected function applyPayAtVenueRestriction(User $customer): void
    {
        $limit = (int) setting('payments.pay_at_venue_cancellation_limit');
        $customer = $customer->fresh();
        $failures = $customer->payAtVenueFailureCount();

        if ($failures < $limit || $customer->isPayAtVenueBanned()) {
            return;
        }

        $customer->banPayAtVenue($failures);
        $customer->notify(new CriticalNotification(
            'Pay at Venue has been restricted on your account',
            "Pay at Venue has been restricted after {$failures} cancellations or no-shows. A Super Administrator can restore it after reviewing your account.",
            'danger',
            mailLines: [
                "Pay at Venue has been restricted after {$failures} recorded cancellations or no-shows.",
                'You can still use other available payment methods. Contact support if you believe this restriction was applied in error.',
            ],
        ));

        foreach (User::where('role_id', Role::SuperAdministrator)->get() as $admin) {
            $admin->notify(new BookingNotification(
                "{$customer->name} was automatically restricted from Pay at Venue after {$failures} cancellations or no-shows. Review the account if they request restoration.",
                'system',
                null,
                route('filament.admin.resources.users.index'),
            ));
        }
    }

    protected function assertWithinRules(Service $service, CarbonInterface $start, int $slots): void
    {
        if ($slots < $service->min_slots) {
            throw new SlotUnavailableException("Minimum booking is {$service->min_slots} × {$service->slotLabel()}.");
        }
        if ($service->max_slots && $slots > $service->max_slots) {
            throw new SlotUnavailableException("Maximum booking is {$service->max_slots} × {$service->slotLabel()}.");
        }
        if ($start->lt(now()->addMinutes($service->lead_time_minutes))) {
            throw new SlotUnavailableException('Bookings must be made at least '.minutes_label($service->lead_time_minutes).' in advance.');
        }

        $maxDays = (int) setting('bookings.max_days_ahead');
        if ($start->gt(today()->addDays($maxDays)->endOfDay())) {
            throw new SlotUnavailableException("Bookings can be made up to {$maxDays} days ahead.");
        }

        $day = $this->availability->sessionDayFor($service, $start);
        if (! $day) {
            $window = $service->windowFor($start->dayOfWeek);
            throw new SlotUnavailableException($window
                ? "That time is outside opening hours ({$window[0]} – {$window[1]})."
                : 'The venue is closed on that day.');
        }

        [$dayOpen, $dayClose] = $this->availability->windowBounds($service, $day);
        $end = $start->copy()->addMinutes($slots * $service->slot_minutes);
        if ($end->gt($dayClose)) {
            throw new SlotUnavailableException('That booking would run past closing time ('.$dayClose->format('h:i A').').');
        }

        if ($dayOpen->diffInMinutes($start, true) % $service->slot_minutes !== 0) {
            throw new SlotUnavailableException('Start time must align with the '.$service->slotLabel().' booking blocks.');
        }
    }
}
