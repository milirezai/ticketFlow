<?php

namespace App\Listeners\Ticket\Activity;

use App\Events\Activity\TicketAssigned;
use App\Models\User\User;
use App\Notifications\Ticket\Activity\TicketAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTicketAssignedNotification implements ShouldQueue
{
    use InteractsWithQueue;
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
    public function handle(TicketAssigned $event): void
    {
        $ticket = $event->data['subject'];
        $description = $event->data['description'];
        $newExpertId = $event->data['properties']['new'];
        $newExpert = User::find($newExpertId);
        if ($newExpert) {
            $newExpert->notify(
                new TicketAssignedNotification($ticket, $description)
            );
        }
    }
}
