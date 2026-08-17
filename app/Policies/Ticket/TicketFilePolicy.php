<?php

namespace App\Policies\Ticket;

use App\Models\Ticket\TicketFile;
use App\Models\User\User;

class TicketFilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, TicketFile $ticketFile): bool
    {
        return $ticketFile->ticket->user_id === $user->id || $ticketFile->ticket->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TicketFile $ticketFile): bool
    {
        return $ticketFile->ticket->user_id === $user->id || $ticketFile->ticket->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, TicketFile $ticketFile): bool
    {
        return $ticketFile->ticket->user_id === $user->id || $ticketFile->ticket->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TicketFile $ticketFile): bool
    {
        return $ticketFile->ticket->user_id === $user->id || $ticketFile->ticket->assigned_to === $user->id;
    }
}
