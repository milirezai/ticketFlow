<?php

namespace App\Http\Resources\Api\V1\Dashboard;

use App\Http\Resources\Api\V1\Ticket\TicketCategoryResource;
use App\Http\Resources\Api\V1\Ticket\TicketPriorityResource;
use App\Http\Resources\Api\V1\Ticket\TicketResource;
use App\Http\Resources\Api\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
          return [
            'total_tickets' => $this->resource['total_tickets'],
            'open_tickets' => [
                'count' => $this->resource['open']->total(),
                'tickets' => TicketResource::collection($this->resource['open']),
            ],
            'pending_tickets' => [
                'count' => $this->resource['pending']->total(),
                'tickets' => TicketResource::collection($this->resource['pending']),
            ],
            'closed_tickets' => [
                'count' => $this->resource['closed']->total(),
                'tickets' => TicketResource::collection($this->resource['closed']),
            ],
            'by_priority' => $this->resource['by_priority']->map(fn ($priority) => [
                'priority' => TicketPriorityResource::make($priority),
                'count' => $priority->tickets_count,
            ]),
            'by_category' => $this->resource['by_category']->map(fn ($category) => [
                'category' => TicketCategoryResource::make($category),
                'count' => $category->tickets_count,
            ]),
            'per_expert' => $this->resource['per_expert']->map(fn ($expert) => [
                'expert' => UserResource::make($expert),
                'count' => $expert->assigned_tickets_count,
            ]),
        ];
    }
}
