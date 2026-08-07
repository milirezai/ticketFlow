<?php

namespace App\Policies;


use App\Models\Ticket\Ticket as TicketModel;
use App\Models\User\User;

class TicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ticket.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TicketModel $ticket): bool
    {
        return $user->hasPermission('ticket.view') && ($ticket->user_id === $user->id || $ticket->assigned_to === $user->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('ticket.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TicketModel $ticket): bool
    {
        return $user->hasPermission('ticket.update') && ($ticket->user_id === $user->id || $ticket->assigned_to === $user->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TicketModel $ticket): bool
    {
        return $user->hasPermission('ticket.delete')
            && ($ticket->user_id === $user->id || $user->hasRole('manager'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TicketModel $ticket): bool
    {
        return $user->hasPermission('ticket.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TicketModel $ticket): bool
    {
        return $user->hasPermission('ticket.forceDelete') && ($ticket->user_id === $user->id || $user->hasRole('manager'));
    }

    /**
     * Determine whether the user can assign the ticket to someone.
     */
    public function assign(User $user, TicketModel $ticket): bool
    {
        return $user->hasPermission('ticket.assign');
    }

    /**
     * Determine whether the user can answer the ticket.
     */
    public function answer(User $user, TicketModel $ticket): bool
    {
        return $user->hasPermission('ticket.answer')
            && ($ticket->user_id === $user->id || $ticket->assigned_to === $user->id);
    }

    /**
     * Determine whether the user can close the ticket.
     */
    public function close(User $user, TicketModel $ticket): bool
    {
        return $user->hasPermission('ticket.close')
            && ($ticket->user_id === $user->id || $ticket->assigned_to === $user->id);
    }
}
