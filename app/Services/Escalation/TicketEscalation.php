<?php

namespace App\Services\Escalation;

use App\Decisions\TicketEscalatedDecision;
use App\Decisions\TicketFirstEscalatedDecision;
use App\Decisions\LowPriorityTicketEscalatedTimeDecision;
use App\Decisions\MediumPriorityTicketEscalatedTimeDecision;
use App\Decisions\HighPriorityTicketEscalatedTimeDecision;
use App\Decisions\CriticalPriorityTicketEscalatedTimeDecision;
use App\Decisions\ExpertTicketsShouldReviewDecision;
use App\Events\Activity\TicketAssigned;
use App\Models\Ticket\Ticket;
use App\Models\User\User;
use Milirulepilot\RulePilot;

class TicketEscalation
{

    public function escalate(): void
    {
        Ticket::all()->filter(function ($ticket){
            return $this->eligibleTicketsForEscalate($ticket);
        })->map(function ($ticket){
            $nexExpert = $this->nextExpert($ticket);
            $this->log($ticket, $nexExpert);
            $ticket->update([
                'assigned_to' => $nexExpert,
                'last_escalation' => now()->addHours($this->escalationTime($ticket))
            ]);
            return 'escalate from ticket # '.$ticket->id;
        });
    }
    private function eligibleTicketsForEscalate(Ticket $ticket): bool
    {
        if ($ticket->last_escalation == null)
            return $this->firstEscalate($ticket);
        return app(RulePilot::class)->evaluate(TicketEscalatedDecision::class,[
            [
                'field' => 'ticketStatus',
                'value' => $ticket->status->name
            ],
            [
                'field' => 'last_escalation',
                'value' => $ticket->last_escalation
            ]
        ])->matched();
    }
    private function firstEscalate(Ticket $ticket): bool
    {
        return app(RulePilot::class)->evaluate(TicketFirstEscalatedDecision::class,[
            [
                'field' => 'ticketStatus',
                'value' => $ticket->status->name
            ]
        ])->matched();
    }

    private function escalationTime(Ticket $ticket): int
    {
        return app(RulePilot::class)->evaluate($this->decisionFind($ticket),[
            [
                'field' => 'priorityName',
                'value' => $ticket->priority->name
            ]
        ])->decisionResult();
    }
    private function decisionFind(Ticket $ticket): string
    {
        return match ($ticket->priority->name){
            'low' => LowPriorityTicketEscalatedTimeDecision::class,
            'medium' => MediumPriorityTicketEscalatedTimeDecision::class,
            'high' => HighPriorityTicketEscalatedTimeDecision::class,
            'critical' => CriticalPriorityTicketEscalatedTimeDecision::class,
            default => throw new \Exception('no define priority')
        };
    }
    private function nextExpert(Ticket $ticket): int
    {
        $user = User::all()->filter(function ($user) use ($ticket){
            return $this->eligibleExpert($user, $ticket);
        })->filter(function ($user) use ($ticket){
            return $this->oldExpertNotSelected($user,$ticket);
        });

        if ($user->isEmpty())
            return $this->ifNoEligibleExpert();

        return $user->random()->id;
    }
    private function eligibleExpert(User $user, Ticket $ticket): bool
    {
        if ($user->isExpert())
            return false;

        $canReview = app(RulePilot::class)->evaluate(ExpertTicketsShouldReviewDecision::class,[
            [
                'field' => 'ticketsShouldReview',
                'value' => $user->ticketsShouldReview()->count()
            ]
        ])->matched();

        if ($canReview)
            return $this->expertOnCategoryTicket($user,$ticket);
        else
            return false;
    }
    private function expertOnCategoryTicket(User $user, Ticket $ticket): bool
    {
        $expert = $user->expertCategories()->get()->map(function ($expertCategory) use ($ticket){
            return $expertCategory->name == $ticket->category->name;
        })->filter(function ($expertCategory){
            return $expertCategory;
        });
        if ($expert->isEmpty())
            return false;
        return $expert->first();
    }
    private function oldExpertNotSelected(User $user, Ticket $ticket): bool
    {
        return $user->id != $ticket->assignedTo->id;
    }
    private function ifNoEligibleExpert(): int
    {
        return User::all()->map(function ($user){
            return $user->hasRole('support-specialist');
        })->filter(function ($user){
            return $user;
        })->keys()->random();
    }
    private function log(Ticket $ticket, $expert): void
    {
        event(new TicketAssigned([
            'action' => 'ticket.assigned',
            'subject' => $ticket,
            'description' => ' change expert for ticket #'.$ticket->id. ' from '. $ticket->assignedTo?->id. ' to '. $expert,
            'properties' => [
                'old' => $ticket->assignedTo?->id,
                'new' => $expert,
            ]
        ]));
    }
}
