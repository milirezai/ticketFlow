<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Dashboard\DashboardResource;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): DashboardResource
    {
        if (!$request->user()->hasRole('manager') && !$request->user()->hasRole('super-admin')) {
            abort(403);
        }
        $eagerLoads = ['user', 'assignedTo', 'category', 'priority', 'status'];
        return DashboardResource::make([
            'total_tickets' => Ticket::count(),
            'open' => Ticket::where('ticket_status_id', TicketStatus::where('name', 'open')->first()?->id)
                ->with($eagerLoads)->paginate(10),
            'pending' => Ticket::where('ticket_status_id', TicketStatus::where('name', 'pending')->first()?->id)
                ->with($eagerLoads)->paginate(10),
            'closed' => Ticket::where('ticket_status_id', TicketStatus::where('name', 'closed')->first()?->id)
                ->with($eagerLoads)->paginate(10),
            'by_priority' => TicketPriority::withCount('tickets')->get(),
            'by_category' => TicketCategory::withCount('tickets')->get(),
            'per_expert' => User::has('assignedTickets')
                ->withCount('assignedTickets')
                ->get(),
        ]);
    }
}
