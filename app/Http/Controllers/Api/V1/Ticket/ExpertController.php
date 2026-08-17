<?php

namespace App\Http\Controllers\Api\V1\Ticket;

use App\Events\Activity\TicketAssigned;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ticket\AssignTicketRequest;
use App\Http\Requests\Api\V1\Ticket\ExpertCategoryRequest;
use App\Http\Resources\Api\V1\Ticket\ExpertResource;
use App\Http\Resources\Api\V1\Ticket\TicketCategoryResource;
use App\Http\Resources\Api\V1\Ticket\TicketResource;
use App\Models\Ticket\Ticket;
use App\Models\User\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpertController extends Controller
{
    use AuthorizesRequests;

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);
        $experts = User::whereHas('roles', fn($q) => $q->where('name', 'expert'))->with('expertCategories')->get();
        return ExpertResource::collection($experts);
    }

    public function tickets(User $expert): AnonymousResourceCollection
    {
        $this->authorize('viewTickets', $expert);
        $tickets = $expert->assignedTickets()->with(['user', 'assignedTo', 'category', 'status', 'priority'])->get();
        return TicketResource::collection($tickets);
    }

    public function categories(User $expert): AnonymousResourceCollection
    {
        $this->authorize('viewCategories', $expert);
        return TicketCategoryResource::collection($expert->expertCategories()->get());
    }

    public function syncCategories(User $expert, ExpertCategoryRequest $request): AnonymousResourceCollection
    {
        $this->authorize('syncCategories', $expert);
        $expert->expertCategories()->sync($request->validated('category_ids', []));
        return TicketCategoryResource::collection($expert->expertCategories()->get());
    }

    public function assign(User $expert, AssignTicketRequest $request): TicketResource
    {
        $this->authorize('assign', $expert);
        $ticket = Ticket::findOrFail($request->validated('ticket_id'));

        event(new TicketAssigned([
            'action' => 'ticket.assigned',
            'user' => $request->user()->id,
            'subject' => $ticket,
            'description' => ' change expert for ticket #'.$ticket->id. ' from '. $ticket->assignedTo->id. ' to '. $expert->id,
            'properties' => [
                'old' => $ticket->assignedTo->id,
                'new' => $expert->id,
            ]
        ]));

        $ticket->update(['assigned_to' => $expert->id]);



        return TicketResource::make($ticket->fresh()->load(['user', 'assignedTo', 'category', 'status', 'priority']));
    }
}
