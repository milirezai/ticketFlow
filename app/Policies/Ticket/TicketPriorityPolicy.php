<?php

namespace App\Policies\Ticket;

use Illuminate\Support\Facades\Gate;

class TicketPriorityPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        return Gate::allows('ticket.priority.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(): bool
    {
        return Gate::allows('ticket.priority.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(): bool
    {
        return Gate::allows('ticket.priority.delete');
    }
}
