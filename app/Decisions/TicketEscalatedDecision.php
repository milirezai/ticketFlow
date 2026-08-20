<?php

namespace App\Decisions;

use Milirulepilot\Decision\Decision;

class TicketEscalatedDecision extends Decision
{

    public function name(): string
    {
        return 'ticketEscalated';
    }

    public function conditions(): array
    {
       return [
           $this->condition->field('ticketStatus')->equal('open')->stopOrFail()->make(),
           $this->condition->field('created_at')->greaterThan(now())->make()
       ];
    }

    public function result(): mixed
    {
        return true;
    }
}
