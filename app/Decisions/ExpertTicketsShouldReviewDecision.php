<?php

namespace App\Decisions;

use Milirulepilot\Decision\Decision;

class ExpertTicketsShouldReviewDecision extends Decision
{

    public function name(): string
    {
        return 'expertTicketsShouldReviewDecision';
    }

    public function conditions(): array
    {
       return [
           $this->condition->field('ticketsShouldReview')->greaterThan(5)->make()
       ];
    }

    public function result(): mixed
    {
        return true;
    }
}
