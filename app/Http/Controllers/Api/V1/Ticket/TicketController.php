<?php

namespace App\Http\Controllers\Api\V1\Ticket;

use App\Events\Activity\TicketAttachment;
use App\Events\Activity\TicketCategoryChanged;
use App\Events\Activity\TicketCreate;
use App\Events\Activity\TicketMessageAdded;
use App\Events\Activity\TicketPriorityChanged;
use App\Events\Activity\TicketStatusChanged;
use App\Filters\TicketFilter;
use App\Http\Resources\Api\V1\Activity\ActivityLogResource;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPriority;
use App\Services\ResponseTime\ResponseTimer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use App\Http\Requests\Api\V1\Ticket\TicketRequest;
use App\Http\Resources\Api\V1\Ticket\TicketResource;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketFile;
use App\Models\Ticket\TicketMessage;
use App\Models\Ticket\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    use AuthorizesRequests;
    public function __construct(ResponseTimer $responseTimer)
    {
        $this->authorizeResource(Ticket::class);
        $responseTimer->evaluateTime();
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
        event(new TicketCreate([
            'action' => 'ticket.create',
            'user' => $ticket->user->id,
            'subject' => $ticket,
            'description' => $request->user()->first_name.' create a new ticket #'.$ticket->id,
            'properties' => [
                'category' => $ticket->category->name,
                'priority' => $ticket->priority->name,
                'status' => $ticket->status->name
            ]
        ]));


        $messageInputs = [
            'content' => $request->input('content'),
            'user_id' => $request->user()->id,
            'ticket_id' => $ticket->id,
            'status' => true
        ];
        $message = TicketMessage::create($messageInputs);
        event(new TicketMessageAdded([
            'action' => 'ticket.message_add',
            'user' => $request->user()->id,
            'subject' => $ticket,
            'description' => $request->user()->first_name.' create a message for ticket #'.$ticket->id,
            'properties' => [
                'message_id' => $message->id
            ]
        ]));

        foreach ($request->file('files', []) as $file) {
            $name =  Str::of($file->getClientOriginalName())->slug() . '_' . time() . '_' . Str::random(4) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('tickets/' . $ticket->id, $name, 'local');
            TicketFile::create([
                'user_id' => $request->user()->id,
                'ticket_id' => $request->user()->id,
                'path' => $path,
                'type' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
                'status' => true,
            ]);
        }
        if ($request->has('files'))
            event(new TicketAttachment([
                'action' => 'ticket.file_attach',
                'user' => $request->user()->id,
                'subject' => $ticket,
                'description' => $request->user()->first_name.' attach a file for ticket #'.$ticket->id,
                'properties' => []
            ]));

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

        $request->whenFilled('ticket_status_id',function ($ticket_status_id) use ($ticket, $request){
            $new_status = TicketStatus::where('id',$request->input('ticket_status_id'))->get()->first()->name;
            event(new TicketStatusChanged([
                'action' => 'ticket.status_change',
                'user' => $request->user()->id,
                'subject' => $ticket,
                'description' => $request->user()->first_name.' change status for ticket #'.$ticket->id. ' from '. $ticket->status->name. ' to '.$new_status,
                'properties' => [
                    'old' => $ticket->status->name,
                    'new' => $new_status,
                ]
            ]));
        });
        $request->whenFilled('ticket_priority_id',function ($ticket_priority_id) use ($ticket,$request){
            $new_priority = TicketPriority::where('id',$request->input('ticket_priority_id'))->get()->first()->name;
            event(new TicketPriorityChanged([
                'action' => 'ticket.priority_change',
                'user' => $request->user()->id,
                'subject' => $ticket,
                'description' => $request->user()->first_name.' change priority for ticket #'.$ticket->id. ' from '. $ticket->priority->name. ' to '.$new_priority,
                'properties' => [
                    'old' => $ticket->priority->name,
                    'new' => $new_priority,
                ]
            ]));
        });
        $request->whenFilled('ticket_category_id',function ($ticket_category_id) use ($ticket,$request){
            $new_category = TicketCategory::where('id',$request->input('ticket_category_id'))->get()->first()->name;
            event(new TicketCategoryChanged([
                'action' => 'ticket.catefory_change',
                'user' => $request->user()->id,
                'subject' => $ticket,
                'description' => $request->user()->first_name.' change category for ticket #'.$ticket->id. ' from '. $ticket->category->name. ' to '.$new_category,
                'properties' => [
                    'old' => $ticket->category->name,
                    'new' => $new_category,
                ]
            ]));
        });

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
    public function activities(Ticket $ticket)
    {
        $this->authorize('ticket.view');
        return ActivityLogResource::collection($ticket->activities);
    }
}
