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
use Exception;

class TicketEscalation
{

    public function escalate(): void
    {
        // in develop
        $titi =Ticket::all()->filter(function ($ticket){
            dd($this->nextExpert($ticket));
//            return $this->eligibleTicketsForEscalate($ticket);
        })->map(function ($ticket){
            dd($this->nextExpert($ticket));
            $ticket->update([
                'assigned_to' => $this->nextExpert($ticket),
                'last_escalation' => now()->addHours($this->escalationTime($ticket))
            ]);
            return "as ticketId ".$ticket->id;
        });
        dd($titi);
    }
    private function eligibleTicketsForEscalate(Ticket $ticket)
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
    private function nextExpert(Ticket $ticket)
    {
        // in develop
        $user = User::all()->filter(function ($user){
            return (bool) $user->isExpert();
        })->filter(function ($user){
            return app(RulePilot::class)->evaluate(ExpertTicketsShouldReviewDecision::class,[
                [
                    'field' => 'ticketsShouldReview',
                    'value' => $user->ticketsShouldReview()->count()
                ]
            ])->matched();
        })->filter(function ($user) use ($ticket){
            return $user->expertCategories()->get()->map(function ($userExpertCategory) use ($ticket){
                return $userExpertCategory->name == $ticket->category->name;
            });
        });
        dd($user->count());
        return $user;
    }
    private function eligibleExpert(): bool
    {

    }
    private function oldExpertNotSelected(): bool
    {

    }
    private function ifNoEligibleExpert()
    {

    }
    private function log(): void
    {

    }
}
