<?php

namespace App\Http\Resources\Api\V1\Ticket;

use App\Http\Resources\Api\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'content' => $this->content,
            'status' => $this->status,
            'user' => UserResource::make($this->whenLoaded('user')),
            'created_at' => $this->created_at->isoFormat('dddd D MMMM Y'),
            'updated_at' => $this->updated_at->isoFormat('dddd D MMMM Y')
        ];
    }
}
