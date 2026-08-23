<?php

namespace App\Listeners\Ticket\Activity;

use App\Events\Activity\TicketStatusChanged;
use App\Notifications\Ticket\Activity\TicketStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTicketStatusChangedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $delay = 60;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TicketStatusChanged $event): void
    {
        $ticket = $event->data['subject'];
        $description = $event->data['description'];
        $ticket->user->notify(
            new TicketStatusChangedNotification($ticket, $description)
        );
    }
}
