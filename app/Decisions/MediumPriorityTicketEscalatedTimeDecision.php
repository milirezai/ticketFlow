<?php

namespace App\Decisions;

use Milirulepilot\Decision\Decision;

class MediumPriorityTicketEscalatedTimeDecision extends Decision
{

    public function name(): string
    {
        return 'mediumPriorityTicketEscalatedTime';
    }

    public function conditions(): array
    {
       return [
           $this->condition->field('priorityName')->equal('medium')->make()
       ];
    }

    public function result(): mixed
    {
        return 24;
    }
}
