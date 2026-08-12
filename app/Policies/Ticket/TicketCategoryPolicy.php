<?php

namespace App\Policies\Ticket;

use Illuminate\Support\Facades\Gate;

class TicketCategoryPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        return Gate::allows('ticket.category.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(): bool
    {
        return Gate::allows('ticket.category.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(): bool
    {
        return Gate::allows('ticket.category.delete');
    }
}
