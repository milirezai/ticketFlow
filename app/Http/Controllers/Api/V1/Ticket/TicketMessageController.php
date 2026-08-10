<?php

namespace App\Http\Controllers\Api\V1\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ticket\TicketMessageRequest;
use App\Http\Resources\Api\V1\Ticket\TicketMessageResource;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketMessage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TicketMessageController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Ticket $ticket)
    {
        $this->authorize('viewAny', $ticket->messages()->make());
        return TicketMessageResource::collection($ticket->messages()->with('user')->oldest()->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketMessageRequest $request, Ticket $ticket)
    {
        $this->authorize('create', $ticket->messages()->make());
        $message = TicketMessage::create([
            ...$request->validated(),
            'status' => 1,
            'user_id' => $request->user()->id,
            'ticket_id' => $ticket->id
        ]);
        return TicketMessageResource::make($message)->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket, TicketMessage $message)
    {
        $this->authorize('view', $message);
        return TicketMessageResource::make($message->load('user'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(TicketMessageRequest $request, Ticket $ticket, TicketMessage $message)
    {
        $this->authorize('update', $message);
        $message->update($request->validated());
        return TicketMessageResource::make($message->fresh()->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket, TicketMessage $message)
    {
        $this->authorize('delete', $message);
        $message->delete();
        return response()->noContent();
    }
}
