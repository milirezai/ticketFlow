<?php

namespace App\Http\Controllers\Api\V1\Ticket;

use App\Filters\TicketFilter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use App\Http\Requests\Api\V1\Ticket\TicketRequest;
use App\Http\Resources\Api\V1\Ticket\TicketResource;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketFile;
use App\Models\Ticket\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    use AuthorizesRequests;
    public function __construct()
    {
        $this->authorizeResource(Ticket::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, TicketFilter $filter)
    {
        $tickets = Ticket::query();

        $filter->search($tickets, $request->only(['title', 'status', 'priority', 'owner', 'category', 'dateFrom', 'dateTo', 'assignedTo']));

        return TicketResource::collection($tickets->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketRequest $request)
    {
        $inputs = $request->validated();
        $inputs['user_id'] = $request->user()->id;
        $ticket = Ticket::create($inputs);

        $messageInputs = [
            'content' => $request->input('content'),
            'user_id' => $request->user()->id,
            'ticket_id' => $ticket->id,
            'status' => true
        ];
        TicketMessage::create($messageInputs);

        foreach ($request->file('files', []) as $file) {
            $name =  Str::of($file->getClientOriginalName())->slug() . '_' . time() . '_' . Str::random(4) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('tickets/' . $ticket->id, $name, 'local');
            TicketFile::create([
                'user_id' => $request->user()->id,
                'ticket_id' => $ticket->id,
                'path' => $path,
                'type' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
                'status' => true,
            ]);
        }
        return TicketResource::make($ticket);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        return TicketResource::make($ticket);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(TicketRequest $request, Ticket $ticket)
    {
        $ticket->update($request->all());
        return TicketResource::make($ticket);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        $ticket->delete();
        return response()->noContent();
    }
}
