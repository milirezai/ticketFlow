<?php

namespace App\Policies\Ticket;

use App\Models\Ticket\Ticket;
use App\Models\User\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class ExpertPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return Gate::allows('expert.manage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function viewTickets(User $user, User $expert): bool
    {
        return Gate::allows('expert.manage') || ($user->hasRole('expert') && $user->id === $expert->id);
    }

    public function viewCategories(User $user, User $expert): bool
    {
        return Gate::allows('expert.manage') || ($user->hasRole('expert') && $user->id === $expert->id);
    }

    public function syncCategories(User $user, User $expert): bool
    {
        return Gate::allows('expert.manage');
    }

    public function assign(User $user, User $expert): bool
    {
        return  Gate::allows('expert.manage') || $user->hasRole('support-specialist');
    }
}
