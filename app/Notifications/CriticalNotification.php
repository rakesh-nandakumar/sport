<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A business-critical event: it must be visible in the notification centre
 * and delivered by email so the recipient does not have to be signed in.
 */
class CriticalNotification extends Notification
{
    /**
     * @param  list<string>  $mailLines
     */
    public function __construct(
        public string $subject,
        public string $message,
        public string $type = 'system',
        public ?Booking $booking = null,
        public ?string $link = null,
        public ?string $actionText = null,
        public array $mailLines = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
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

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject)
            ->greeting('Hello '.$notifiable->name.',');

        foreach ($this->mailLines ?: [$this->message] as $line) {
            $mail->line($line);
        }

        $link = $this->link ?? ($this->booking ? route('bookings.show', $this->booking) : null);
        if ($link) {
            $mail->action($this->actionText ?? 'View details', $link);
        }

        return $mail->salutation('The '.config('app.name').' team');
    }
}
