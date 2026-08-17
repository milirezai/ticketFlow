<?php

namespace App\Http\Resources\Api\V1\Activity;

use App\Http\Resources\Api\V1\Access\PermissionResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\Ticket\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => UserResource::make($this->user),
            'action' => $this->action,
            'description' => $this->description,
            'properties' => $this->properties,
            'created_at' => $this->created_at->isoFormat('dddd D MMMM Y'),
            'updated_at' => $this->created_at->isoFormat('dddd D MMMM Y')
        ];
    }
}
