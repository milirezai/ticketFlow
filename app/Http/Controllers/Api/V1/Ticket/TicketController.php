<?php

namespace App\Http\Controllers\Api\V1\Ticket;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use App\Http\Requests\Api\V1\Ticket\TicketRequest;
use App\Http\Resources\Api\V1\Ticket\TicketResource;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketFile;
use App\Models\Ticket\TicketMessage;
use Illuminate\Http\Request;

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
    public function index()
    {
        $tickets = Ticket::query();

        return TicketResource::collection($tickets->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketRequest $request)
    {
        $inputs = $request->all();
        $inputs['user_id'] = $request->user()->id;
        $ticket = Ticket::create($inputs);

        $messageInputs = [
            'content' => $request->input('content'),
            'user_id' => 1,
            'ticket_id' => $ticket->id,
            'status' => true
        ];
        TicketMessage::create($messageInputs);

        $request->whenHas('file',function ($input) use ($ticket){
            $file = $input;
            $fileSize = $file->getSize();
            $name = time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('file/ticket'),$name);
            $filePath = 'file/ticket/'.$name;
            $fileInputs = [
                'user_id' => 1,
                'ticket_id' => $ticket->id,
                'path' => $filePath,
                'type' => $file->getClientOriginalExtension(),
                'size' => $fileSize,
                'status' => true
            ];
            TicketFile::create($fileInputs);
        });

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
