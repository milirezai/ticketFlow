<?php

namespace App\Notifications\Ticket\Activity;

use App\Models\Ticket\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketMessageCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $delay = 60;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Ticket $ticket, public string $description)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Message on Ticket #{$this->ticket->id}")
            ->greeting("Hello {$notifiable->first_name}")
            ->line($this->description)
            ->action('View Ticket', url("/api/v1/tickets/{$this->ticket->id}"));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
