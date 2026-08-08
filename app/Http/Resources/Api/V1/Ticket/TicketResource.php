<?php

namespace App\Http\Resources\Api\V1\Ticket;

use App\Http\Resources\Api\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'subject' => $this->subject,
            'owner' => UserResource::make($this->whenLoaded('user')),
            'assignedTo' => UserResource::make($this->whenLoaded('assignedTo')),
            'category' => TicketCategoryResource::make($this->whenLoaded('category')),
            'priority' => TicketPriorityResource::make($this->whenLoaded('priority')),
            'status' => TicketStatusResource::make($this->whenLoaded('status')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
