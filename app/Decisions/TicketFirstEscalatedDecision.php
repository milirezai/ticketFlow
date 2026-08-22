<?php

namespace App\Decisions;

use Milirulepilot\Decision\Decision;

class TicketFirstEscalatedDecision extends Decision
{

    public function name(): string
    {
        return 'ticketFirstEscalatedDecision';
    }

    public function conditions(): array
    {
       return [
           $this->condition->field('ticketStatus')->equal('open')->make(),
       ];
    }

    public function result(): mixed
    {
        return true;
    }
}
