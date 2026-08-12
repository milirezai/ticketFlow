<?php

namespace App\Http\Controllers\Api\V1\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ticket\TicketPriorityRequest;
use App\Http\Resources\Api\V1\Ticket\TicketPriorityResource;
use App\Models\Ticket\TicketPriority;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TicketPriorityController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(TicketPriority::class, 'ticket_priority', ['except' => ['index', 'show']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TicketPriorityResource::collection(TicketPriority::paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketPriorityRequest $request)
    {
        $priority = TicketPriority::create($request->validated());
        return TicketPriorityResource::make($priority)->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TicketPriority $ticketPriority)
    {
        return TicketPriorityResource::make($ticketPriority);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TicketPriorityRequest $request, TicketPriority $ticketPriority)
    {
        $ticketPriority->update($request->validated());
        return TicketPriorityResource::make($ticketPriority->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TicketPriority $ticketPriority)
    {
        if ($ticketPriority->tickets()->exists()) {
            return response()->json(['message' => 'Cannot delete: tickets reference this record.'], 409);
        }
        $ticketPriority->delete();
        return response()->noContent();
    }
}
