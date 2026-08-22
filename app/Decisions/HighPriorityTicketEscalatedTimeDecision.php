<?php

namespace App\Decisions;

use Milirulepilot\Decision\Decision;

class HighPriorityTicketEscalatedTimeDecision extends Decision
{

    public function name(): string
    {
        return 'highPriorityTicketEscalatedTime';
    }

    public function conditions(): array
    {
       return [
           $this->condition->field('priorityName')->equal('high')->make()
       ];
    }

    public function result(): mixed
    {
        return 12;
    }
}
