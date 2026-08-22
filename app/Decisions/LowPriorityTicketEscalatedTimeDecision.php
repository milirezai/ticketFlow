<?php

namespace App\Decisions;

use Milirulepilot\Decision\Decision;

class LowPriorityTicketEscalatedTimeDecision extends Decision
{

    public function name(): string
    {
        return 'lowPriorityTicketEscalatedTime';
    }

    public function conditions(): array
    {
       return [
           $this->condition->field('priorityName')->equal('low')->make()
       ];
    }

    public function result(): mixed
    {
        return 36;
    }
}
