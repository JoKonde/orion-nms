<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Agent
 */
class AgentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agent_uuid' => $this->agent_uuid,
            'hostname' => $this->hostname,
            'os' => $this->os,
            'os_version' => $this->os_version,
            'architecture' => $this->architecture,
            'agent_version' => $this->agent_version,
            'status' => $this->status?->value,
            'registered_at' => $this->registered_at,
            'last_seen_at' => $this->last_seen_at,
            'device' => new DeviceResource($this->whenLoaded('device')),
            'created_at' => $this->created_at,
        ];
    }
}
