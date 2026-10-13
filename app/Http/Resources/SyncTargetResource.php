<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyncTargetResource extends JsonResource
{
    /**
     * Transform the synchronization target into an array.
     * 
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'status' => $this->status?->value,
            'last_synced_at' => $this->last_synced_at?->toISOString(),
            'last_error' => $this->last_error,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}