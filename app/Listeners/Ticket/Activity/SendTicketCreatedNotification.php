<?php

namespace App\Listeners\Ticket\Activity;

use App\Events\Activity\TicketCreate;
use App\Models\User\User;
use App\Notifications\Ticket\Activity\TicketCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTicketCreatedNotification implements ShouldQueue
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
    public function handle(TicketCreate $event): void
    {
        $ticket = $event->data['subject'];
        $description = $event->data['description'];
        if ($ticket->assigned_to) {
            $ticket->assignedTo->notify(
                new TicketCreatedNotification($ticket, $description)
            );
        } else {
            $adminsAndSupport = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'support-specialist']);
            })->get();
            $adminsAndSupport->each->notify(
                new TicketCreatedNotification($ticket, $description)
            );
        }
    }
}
