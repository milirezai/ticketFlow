<?php

namespace App\Http\Resources\Api\V1\Access;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'created_at' => $this->created_at
        ];
    }
}
