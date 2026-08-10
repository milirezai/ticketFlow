<?php

namespace App\Policies\Ticket;

use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketMessage;
use App\Models\User\User;
use Illuminate\Support\Facades\Gate;

class TicketMessagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, TicketMessage $ticketMessage): bool
    {
        return $ticketMessage->ticket->user_id === $user->id || ($ticketMessage->ticket->assigned_to === $user->id && Gate::allows('conversation.viewAny'));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TicketMessage $ticketMessage): bool
    {
        return $ticketMessage->ticket->user_id === $user->id || ($ticketMessage->ticket->assigned_to === $user->id && Gate::allows('conversation.view'));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, TicketMessage $ticketMessage): bool
    {
        return $ticketMessage->ticket->user_id === $user->id || ($ticketMessage->ticket->assigned_to === $user->id && Gate::allows('conversation.create'));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TicketMessage $ticketMessage): bool
    {
        return  $ticketMessage->ticket->user_id === $user->id || ($ticketMessage->ticket->assigned_to === $user->id && Gate::allows('conversation.update'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TicketMessage $ticketMessage): bool
    {
        return  $ticketMessage->ticket->user_id === $user->id || ($ticketMessage->ticket->assigned_to === $user->id && Gate::allows('conversation.delete'));
    }
}
