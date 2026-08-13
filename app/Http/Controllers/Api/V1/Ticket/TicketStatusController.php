<?php

namespace App\Http\Controllers\Api\V1\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ticket\TicketStatusRequest;
use App\Http\Resources\Api\V1\Ticket\TicketStatusResource;
use App\Models\Ticket\TicketStatus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TicketStatusController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(TicketStatus::class, 'ticket_status', ['except' => ['index', 'show']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TicketStatusResource::collection(TicketStatus::paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketStatusRequest $request)
    {
        $status = TicketStatus::create($request->validated());
        return TicketStatusResource::make($status)->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TicketStatus $ticketStatus)
    {
        return TicketStatusResource::make($ticketStatus);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TicketStatusRequest $request, TicketStatus $ticketStatus)
    {
        $ticketStatus->update($request->validated());
        return TicketStatusResource::make($ticketStatus->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TicketStatus $ticketStatus)
    {
        if ($ticketStatus->tickets()->exists()) {
            return response()->json(['message' => 'Cannot delete: tickets reference this record.'], 409);
        }
        $ticketStatus->delete();
        return response()->noContent();
    }
}
