<?php

namespace App\Jobs;

use App\Services\Escalation\TicketEscalation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TicketEscalationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     */
    public function handle(TicketEscalation $escalation): void
    {
        $escalation->escalate();
    }
}
