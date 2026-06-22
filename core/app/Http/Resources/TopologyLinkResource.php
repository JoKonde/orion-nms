<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TopologyLink */
class TopologyLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_device_id' => $this->source_device_id,
            'target_device_id' => $this->target_device_id,
            'link_type' => $this->link_type?->value,
            'link_status' => $this->link_status?->value,
            'source_interface' => $this->source_interface,
            'target_interface' => $this->target_interface,
            'metadata' => $this->metadata,
            'source_device' => $this->whenLoaded('sourceDevice', fn () => [
                'id' => $this->sourceDevice?->id,
                'name' => $this->sourceDevice?->name,
                'ip' => $this->sourceDevice?->ip_address,
            ]),
            'target_device' => $this->whenLoaded('targetDevice', fn () => [
                'id' => $this->targetDevice?->id,
                'name' => $this->targetDevice?->name,
                'ip' => $this->targetDevice?->ip_address,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
