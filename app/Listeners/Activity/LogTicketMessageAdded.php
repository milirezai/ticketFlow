<?php

namespace App\Listeners\Activity;

use App\Events\Activity\TicketMessageAdded;
use App\Services\Activity\Activity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogTicketMessageAdded implements ShouldQueue
{
    use InteractsWithQueue;

    public $delay = 60;
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
    public function handle(TicketMessageAdded $event): void
    {
        $this->activity->log(
            action: $event->data['action'], user: $event->data['user'],
            subject: $event->data['subject'], description: $event->data['description'],
            properties: $event->data['properties']
        );
    }
}
