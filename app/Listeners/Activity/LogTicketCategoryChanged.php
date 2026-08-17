<?php

namespace App\Listeners\Activity;

use App\Events\Activity\TicketCategoryChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogTicketCategoryChanged
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
    public function handle(TicketCategoryChanged $event): void
    {
        //
    }
}
