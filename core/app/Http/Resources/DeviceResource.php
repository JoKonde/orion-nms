<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * DeviceResource — format JSON standardise pour l'API devices.
 *
 * @mixin \App\Models\Device
 */
class DeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'ip_address' => $this->ip_address,
            'mac_address' => $this->mac_address,
            'type' => $this->type?->value,
            'vendor' => $this->vendor,
            'model' => $this->model,
            'firmware' => $this->firmware,
            'status' => $this->status?->value,
            'discovery_method' => $this->discovery_method?->value,
            'uptime_seconds' => $this->uptime_seconds,
            'description' => $this->description,
            'last_seen_at' => $this->last_seen_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
