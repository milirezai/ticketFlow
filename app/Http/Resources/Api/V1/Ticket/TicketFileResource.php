<?php

namespace App\Http\Resources\Api\V1\Ticket;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketFileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'size' => $this->size,
            'status' => $this->status,
            'created_at' => $this->created_at->isoFormat('dddd D MMMM Y'),
            'download_url' => route('tickets.files.download', ['ticket' => $this->ticket_id, 'file' => $this->id]),
        ];
    }
}
