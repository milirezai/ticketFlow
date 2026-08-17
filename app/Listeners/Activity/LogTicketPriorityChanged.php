<?php

namespace App\Listeners\Activity;

use App\Events\Activity\TicketPriorityChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogTicketPriorityChanged
{
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
    public function handle(TicketPriorityChanged $event): void
    {
        //
    }
}
