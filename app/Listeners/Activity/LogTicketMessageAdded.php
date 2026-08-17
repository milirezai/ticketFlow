<?php

namespace App\Listeners\Activity;

use App\Events\Activity\TicketMessageAdded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogTicketMessageAdded
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
    public function handle(TicketMessageAdded $event): void
    {
        //
    }
}
