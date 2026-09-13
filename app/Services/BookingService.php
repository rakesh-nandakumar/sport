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
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        protected AvailabilityService $availability,
        protected PricingService $pricing,
    ) {}

    /**
     * Reserve a slot. Competing bookings with a lower payment priority are replaced ("bumped");
     * equal or higher priority bookings block the request.
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
            throw new SlotUnavailableException($method->label().' is not available yet. Please choose another payment method.');
        }

        $this->assertWithinRules($service, $start, $slots);

        $end = $start->copy()->addMinutes($slots * $service->slot_minutes);
        $paymentStatus = $method === PaymentMethod::BankTransfer ? PaymentStatus::PendingVerification : PaymentStatus::Unpaid;
        $priority = Booking::priorityFor($method, $paymentStatus);
        $quote = $this->pricing->quote($service, $option, $start, $slots);

        return DB::transaction(function () use ($customer, $service, $option, $start, $end, $slots, $method, $details, $game, $paymentStatus, $priority, $quote) {
            $competing = $this->availability->competingBookings($service, $option, $start, $end);
            $bumpable = $competing->filter(fn (Booking $b) => $b->priority < $priority);
            $blocking = $competing->count() - $bumpable->count();

            if ($blocking >= $option->capacity) {
                throw new SlotUnavailableException('That time is no longer available. Please pick another slot.');
            }

            $toBump = $competing->count() >= $option->capacity
                ? $bumpable->sortBy('priority')->take($competing->count() - $option->capacity + 1)
                : collect();

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
                'customer_name' => $details['customer_name'],
                'customer_phone' => $details['customer_phone'],
                'notes' => $details['notes'] ?? null,
            ]);

            if ($method === PaymentMethod::BankTransfer) {
                Payment::create([
                    'booking_id' => $booking->id,
                    'method' => $method,
                    'amount' => $booking->total,
                    'status' => PaymentStatus::PendingVerification,
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

    public function cancel(Booking $booking, User $actor, ?string $reason = null): void
    {
        $booking->update([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);

        $label = "{$booking->service->name} at {$booking->venue->name} on {$booking->starts_at->format('d M, h:i A')}";

        if ($actor->id === $booking->user_id) {
            $booking->venue->owner->notify(new BookingNotification("Booking {$booking->reference} was cancelled by the customer ({$label}).", 'danger', $booking));
            $booking->user->notify(new BookingNotification("You cancelled booking {$booking->reference} ({$label}).", 'danger', $booking));
        } else {
            $booking->user->notify(new BookingNotification("The venue cancelled your booking {$booking->reference} ({$label}).".($reason ? " Reason: {$reason}" : ''), 'danger', $booking));
        }
    }

    /** Vendor confirmation locks the slot so it can no longer be replaced. */
    public function vendorConfirm(Booking $booking): void
    {
        $booking->update([
            'status' => BookingStatus::Confirmed,
            'vendor_confirmed_at' => now(),
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

        $payment->update(['proof_path' => $path, 'reference' => $reference]);
        $booking->update(['payment_status' => PaymentStatus::PendingVerification]);

        $booking->venue->owner->notify(new BookingNotification("A bank transfer slip was uploaded for {$booking->reference}. Please verify it.", 'system', $booking));
    }

    public function complete(Booking $booking, bool $noShow = false): void
    {
        $booking->update(['status' => $noShow ? BookingStatus::NoShow : BookingStatus::Completed]);
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
            "Your pay-at-venue booking {$victim->reference} for {$victim->service->name} on {$victim->starts_at->format('d M, h:i A')} was replaced by a paid booking. Pay online next time to lock your slot.",
            'danger',
            $victim,
        ));
    }

    protected function notifyCreated(Booking $booking): void
    {
        $when = $booking->starts_at->format('D d M, h:i A');

        $booking->user->notify(new BookingNotification(
            "Booking {$booking->reference} placed: {$booking->service->name} at {$booking->venue->name}, {$when}. ".
            ($booking->payment_method === PaymentMethod::BankTransfer
                ? 'Upload your transfer slip so the venue can verify it.'
                : 'Pay at the venue when you arrive.'),
            'success',
            $booking,
        ));

        $booking->venue->owner->notify(new BookingNotification(
            "New booking {$booking->reference}: {$booking->customer_name} booked {$booking->service->name} ({$booking->option->name}) for {$when} · {$booking->payment_method->label()}.",
            'success',
            $booking,
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
            throw new SlotUnavailableException('Bookings must be made at least '.$service->lead_time_minutes.' minutes in advance.');
        }

        $window = $service->windowFor($start->dayOfWeek);
        if (! $window) {
            throw new SlotUnavailableException('The venue is closed on that day.');
        }

        [$opens, $closes] = $window;
        $dayOpen = Carbon::parse($start->toDateString().' '.$opens);
        $dayClose = Carbon::parse($start->toDateString().' '.$closes);
        if ($dayClose->lte($dayOpen)) {
            $dayClose->addDay();
        }

        $end = $start->copy()->addMinutes($slots * $service->slot_minutes);
        if ($start->lt($dayOpen) || $end->gt($dayClose)) {
            throw new SlotUnavailableException("That time is outside opening hours ({$opens} – {$closes}).");
        }

        $offset = $start->diffInMinutes($dayOpen, true);
        if ($offset % $service->slot_minutes !== 0) {
            throw new SlotUnavailableException('Start time must align with the '.$service->slotLabel().' booking blocks.');
        }
    }
}
