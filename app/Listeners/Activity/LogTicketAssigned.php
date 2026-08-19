<?php

namespace App\Listeners\Activity;

use App\Events\Activity\TicketAssigned;
use App\Services\Activity\Activity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogTicketAssigned
{
    /**
     * Create the event listener.
     */
    public function __construct(protected readonly Activity $activity)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TicketAssigned $event): void
    {
        $this->activity->log(
            action: $event->data['action'], user: $event->data['user'] ?? null,
            subject: $event->data['subject'], description: $event->data['description'],
            properties: $event->data['properties']
        );
    }
}
