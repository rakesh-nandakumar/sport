<?php

namespace App;

use Illuminate\Notifications\Notification;

class SystemMessageNotification extends Notification
{
    public function __construct(
        public string $message,
        public ?string $link,
        public string $icon,
        public string $type)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        if($this->type === 'system_message'){
            return [
                'message' => $this->message,
                'link' => $this->link,
                'icon' => $this->link,
                'type' => $this->link,
            ];

        }

        if($this->type === 'success'){
            return [
                'message' => $this->message,
                'link' => $this->link,
                'icon' => $this->link,
                'type' => $this->link,
            ];

        }

        if($this->type === 'danger'){
            return [
                'message' => $this->message,
                'link' => $this->link,
                'icon' => $this->link,
                'type' => $this->link,
            ];

        }

        return '';




    }

    public function toArray($notifiable): array
    {
        return [

        ];
    }
}
