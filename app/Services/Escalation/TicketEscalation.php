<?php

namespace App\Services\Escalation;

use App\Decisions\TicketEscalatedDecision;
use App\Decisions\LowPriorityTicketEscalatedTimeDecision;
use App\Decisions\MediumPriorityTicketEscalatedTimeDecision;
use App\Decisions\HighPriorityTicketEscalatedTimeDecision;
use App\Decisions\CriticalPriorityTicketEscalatedTimeDecision;
use App\Events\Activity\TicketAssigned;
use App\Models\Ticket\Ticket;
use App\Models\User\User;
use Illuminate\Support\Facades\Log;
use Milirulepilot\RulePilot;


class TicketEscalation
{
    public function __construct(
        protected readonly RulePilot $rulePilot
    ){}

    public function escalate(): void
    {

    }
    private function eligibleTicketsForEscalate(Ticket $ticket): bool
    {

    }
    private function escalationTime(string $priority): mixed
    {

    }
    private function nextExpert(): int
    {

    }
    private function EligibleExpert()
    {

    }
    private function oldExpertNotSelected(): int
    {

    }
    private function ifNoEligibleExpert()
    {

    }
    private function log(): void
    {

    }
}
