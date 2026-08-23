<?php

namespace App\Listeners\Ticket\Activity;

use App\Events\Activity\TicketMessageAdded;
use App\Notifications\Ticket\Activity\TicketMessageCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTicketMessageCreatedNotification implements ShouldQueue
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
    public function handle(TicketMessageAdded $event): void
    {
        $ticket = $event->data['subject'];
        $senderId = $event->data['user'];
        $description = $event->data['description'];
        $recipients = collect();
        if ($senderId === $ticket->user_id && $ticket->assigned_to) {
            $recipients->push($ticket->assignedTo);
        } elseif ($senderId === $ticket->assigned_to) {
            $recipients->push($ticket->user);
        } else {
            if ($ticket->assigned_to) {
                $recipients->push($ticket->assignedTo);
            }
            $recipients->push($ticket->user);
        }
        $recipients->unique('id')->each->notify(
            new TicketMessageCreatedNotification($ticket, $description)
        );
    }
}
