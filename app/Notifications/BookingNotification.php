<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

class BookingNotification extends Notification
{
    public function __construct(
        public string $message,
        public string $type = 'system',
        public ?Booking $booking = null,
        public ?string $link = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'type' => $this->type,
            'icon' => match ($this->type) {
                'success' => 'fa-solid fa-circle-check',
                'danger' => 'fa-solid fa-circle-exclamation',
                default => 'fa-solid fa-bell',
            },
            'booking_reference' => $this->booking?->reference,
            'link' => $this->link ?? ($this->booking ? route('bookings.show', $this->booking) : null),
        ];
    }
}
