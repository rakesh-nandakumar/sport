<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\Game;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceOption;
use App\Models\User;
use App\Notifications\BookingNotification;
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
        ?Game $game = null,
    ): Booking {
        if (! $method->isAvailable()) {
            throw new SlotUnavailableException($method->label().' is not available right now. Please choose another payment method.');
        }
        if ($method === PaymentMethod::PayAtVenue && $customer->fresh()->isPayAtVenueBanned()) {
            throw new SlotUnavailableException('Pay at Venue is unavailable for this account. Please contact support if you believe this is a mistake.');
        }
        if ($option->service_id !== $service->id) {
            throw new SlotUnavailableException('That option does not belong to this service.');
        }
        if (! $service->is_active || ! $service->venue->isLive()) {
            throw new SlotUnavailableException('This service is not taking bookings at the moment.');
        }

        $this->expireStaleHolds();
        $this->assertWithinRules($service, $start, $slots);

        $end = $start->copy()->addMinutes($slots * $service->slot_minutes);
        $paymentStatus = $method === PaymentMethod::BankTransfer ? PaymentStatus::PendingVerification : PaymentStatus::Unpaid;
        $priority = Booking::priorityFor($method, $paymentStatus);
        $quote = $this->pricing->quote($service, $option, $start, $slots);
        $holdExpiresAt = $method === PaymentMethod::BankTransfer
            ? now()->addMinutes((int) setting('payments.bank_transfer_hold_minutes'))
            : null;

        return DB::transaction(function () use ($customer, $service, $option, $start, $end, $slots, $method, $details, $game, $paymentStatus, $priority, $quote, $holdExpiresAt) {
            $competing = $this->availability->competingBookings($service, $option, $start, $end, lock: true);
            $toBump = $this->resolveCapacity($service, $option, $start, $slots, $priority, $competing);

            $booking = Booking::create([
                'user_id' => $customer->id,
                'venue_id' => $service->venue_id,
                'service_id' => $service->id,
                'service_option_id' => $option->id,
                'game_id' => $game?->id,
                'starts_at' => $start,
                'ends_at' => $end,
                'slots' => $slots,
                'players' => $details['players'] ?? null,
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

            if ($method === PaymentMethod::BankTransfer) {
                Payment::create([
                    'booking_id' => $booking->id,
                    'method' => $method,
                    'amount' => $booking->total,
                    'status' => PaymentStatus::PendingVerification,
                    'reference' => $details['bank_reference'] ?? null,
                ]);
            }

            foreach ($toBump as $victim) {
                $this->bump($victim, $booking);
            }

            $booking->load(['venue.owner', 'service', 'option', 'game']);
            $this->notifyCreated($booking);

            return $booking;
        });
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
        $isPayAtVenueFailure = $actor->id === $booking->user_id
            && $booking->payment_method === PaymentMethod::PayAtVenue;

        $booking->update([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
            'pay_at_venue_failure_at' => $isPayAtVenueFailure ? now() : null,
        ]);

        $label = "{$booking->service->name} at {$booking->venue->name} on {$booking->starts_at->format('d M, h:i A')}";

        if ($actor->id === $booking->user_id) {
            $booking->venue->owner->notify(new BookingNotification("Booking {$booking->reference} was cancelled by the customer ({$label}).", 'danger', $booking));
            $booking->user->notify(new BookingNotification("You cancelled booking {$booking->reference} ({$label}).", 'danger', $booking));
            if ($isPayAtVenueFailure) {
                $this->applyPayAtVenueRestriction($booking->user);
            }
        } else {
            $booking->user->notify(new BookingNotification("The venue cancelled your booking {$booking->reference} ({$label}).".($reason ? " Reason: {$reason}" : ''), 'danger', $booking));
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

        $booking->user->notify(new BookingNotification("{$booking->venue->name} confirmed your booking {$booking->reference}. Your slot is now locked in.", 'success', $booking));
    }

    public function markPaid(Booking $booking, User $verifier, ?string $reference = null): void
    {
        DB::transaction(function () use ($booking, $verifier, $reference) {
            $payment = $booking->payments()->latest()->first() ?? new Payment([
                'booking_id' => $booking->id,
                'method' => $booking->payment_method,
                'amount' => $booking->total,
            ]);

            $payment->fill([
                'status' => PaymentStatus::Paid,
                'reference' => $reference ?: $payment->reference,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
            ])->save();

            $booking->update([
                'payment_status' => PaymentStatus::Paid,
                'status' => BookingStatus::Confirmed,
                'hold_expires_at' => null,
                'priority' => 3,
            ]);
        });

        $booking->user->notify(new BookingNotification("Payment for {$booking->reference} was verified. Your booking is confirmed.", 'success', $booking));
    }

    public function attachProof(Booking $booking, string $path, ?string $reference): void
    {
        $payment = $booking->payments()->latest()->first() ?? Payment::create([
            'booking_id' => $booking->id,
            'method' => $booking->payment_method,
            'amount' => $booking->total,
            'status' => PaymentStatus::PendingVerification,
        ]);

        $payment->update(['proof_path' => $path, 'reference' => $reference ?: $payment->reference]);
        $booking->update(['payment_status' => PaymentStatus::PendingVerification]);

        $deadline = $booking->hold_expires_at ? ' Please verify before '.$booking->hold_expires_at->format('h:i A').' or the slot will be released.' : '';
        $booking->venue->owner->notify(new BookingNotification("A bank transfer slip was uploaded for {$booking->reference}.{$deadline}", 'system', $booking));
    }

    public function complete(Booking $booking, bool $noShow = false): void
    {
        $isPayAtVenueFailure = $noShow && $booking->payment_method === PaymentMethod::PayAtVenue;
        $booking->update([
            'status' => $noShow ? BookingStatus::NoShow : BookingStatus::Completed,
            'pay_at_venue_failure_at' => $isPayAtVenueFailure ? now() : null,
        ]);

        if ($isPayAtVenueFailure) {
            $this->applyPayAtVenueRestriction($booking->user);
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

        foreach ($stale as $booking) {
            $booking->update([
                'status' => BookingStatus::Expired,
                'cancelled_at' => now(),
                'cancel_reason' => 'Bank transfer was not verified in time',
            ]);
            $booking->payments()->where('status', PaymentStatus::PendingVerification)->update(['status' => PaymentStatus::Unpaid]);

            $when = $booking->starts_at->format('d M, h:i A');
            $booking->user->notify(new BookingNotification(
                "Booking {$booking->reference} ({$booking->service->name}, {$when}) expired because the bank transfer was not verified within {$minutes} minutes. Its QR code is no longer valid and the slot is open again: book it once more and upload your slip straight away, or choose Pay at Venue.",
                'danger',
                $booking,
            ));
            $booking->venue->owner->notify(new BookingNotification(
                "Bank transfer hold {$booking->reference} ({$when}) expired unverified and the slot was released.",
                'system',
                $booking,
            ));
        }

        return $stale->count();
    }

    protected function bump(Booking $victim, Booking $winner): void
    {
        $victim->update([
            'status' => BookingStatus::Bumped,
            'bumped_by_booking_id' => $winner->id,
            'cancelled_at' => now(),
            'cancel_reason' => 'Replaced by a higher-priority payment',
        ]);

        $victim->user->notify(new BookingNotification(
            "Your pay-at-venue booking {$victim->reference} for {$victim->service->name} on {$victim->starts_at->format('d M, h:i A')} was replaced by a paid booking. Its QR code is no longer valid. Book another time or choose a paid method next time to lock your slot.",
            'danger',
            $victim,
        ));
    }

    protected function notifyCreated(Booking $booking): void
    {
        $when = $booking->starts_at->format('D d M, h:i A');

        $next = $booking->payment_method === PaymentMethod::BankTransfer
            ? 'The venue has until '.$booking->hold_expires_at->format('h:i A').' to verify your transfer slip.'
            : 'Show the QR code when you arrive and pay at the venue.';

        $booking->user->notify(new BookingNotification(
            "Booking {$booking->reference} placed: {$booking->service->name} at {$booking->venue->name}, {$when}. {$next}",
            'success',
            $booking,
        ));

        $booking->venue->owner->notify(new BookingNotification(
            "New booking {$booking->reference}: {$booking->customer_name} booked {$booking->service->name} ({$booking->option->name}) for {$when} · {$booking->payment_method->label()}.".
            ($booking->hold_expires_at ? ' Verify the transfer before '.$booking->hold_expires_at->format('h:i A').'.' : ''),
            'success',
            $booking,
        ));
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
        $customer->notify(new BookingNotification(
            "Pay at Venue has been restricted after {$failures} cancellations or no-shows. A Super Administrator can restore it after reviewing your account.",
            'danger',
        ));
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
