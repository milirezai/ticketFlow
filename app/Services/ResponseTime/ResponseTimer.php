<?php

namespace App\Services\ResponseTime;

use App\Events\Activity\TicketAssigned;
use App\Models\Ticket\Ticket;
use App\Models\User\User;

class ResponseTimer
{
    protected array $responseTimePriority = [
      'low' => 48,
      'medium' => 24,
      'high' => 12,
     'critical' => 6
    ];
    public function evaluateTime(): void
    {
         Ticket::all()->filter(function ($ticket){
             return $ticket->status->name == 'open';
         })->filter(function ($ticket){
             return $ticket->created_at->addHours($this->responseTime($ticket->priority->name)) < now();
         })->map(function ($ticket){
             $newExpert = $this->newExpert($ticket);
             $ticket->update([
                 'assigned_to' => $newExpert,
                 'created_at' => now()->addHours($this->responseTime($ticket->priority->name))
             ]);
             event(new TicketAssigned([
                 'action' => 'ticket.assigned',
                 'subject' => $ticket,
                 'description' => ' change expert for ticket #'.$ticket->id. ' from '. $ticket->assignedTo?->id. ' to '. $newExpert,
                 'properties' => [
                     'old' => $ticket->assignedTo?->id,
                     'new' => $newExpert,
                 ]
             ]));
         });
    }
    protected function newExpert(Ticket $ticket)
    {
        return User::all()->filter(function ($user){
            return $user->isExpert() == true;
        })->filter(function ($user){
            return $user->ticketsShouldReview()->count() < 5;
        })->filter(function ($user) use ($ticket){
            return $user->expertCategories()->get()->map(function ($userExpertCategory) use ($ticket){
                return $userExpertCategory->name == $ticket->category->name;
            });
        })->filter(function ($user) use ($ticket){
            return $user->id != $ticket->assignedTo->id;
        })->random()?->id;
    }
    protected function responseTime(string $ticketPriority): int
    {
        $time = '';
        foreach ($this->responseTimePriority as $priorityName => $responseTime){
            if ($priorityName == $ticketPriority)
                $time = $responseTime;
        }
        return $time;
    }

}
