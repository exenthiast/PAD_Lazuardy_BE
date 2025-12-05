<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TutorStatusNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $body;
    protected $status;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $body, $status)
    {
        $this->title = $title;
        $this->body = $body;
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status,
        ];
    }
}
