<?php

namespace App\Decisions;

use Milirulepilot\Decision\Decision;

class CriticalPriorityTicketEscalatedTimeDecision extends Decision
{

    public function name(): string
    {
        return 'criticalPriorityTicketEscalatedTime';
    }

    public function conditions(): array
    {
       return [
           $this->condition->field('priorityName')->equal('critical')->make()
       ];
    }

    public function result(): mixed
    {
        return 6;
    }
}
