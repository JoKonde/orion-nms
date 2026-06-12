<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AlertRule */
class AlertRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'rule_type' => $this->rule_type?->value,
            'metric_type' => $this->metric_type?->value,
            'operator' => $this->operator?->value,
            'threshold' => $this->threshold,
            'severity' => $this->severity?->value,
            'device_id' => $this->device_id,
            'device' => $this->whenLoaded('device', fn () => new DeviceResource($this->device)),
            'is_enabled' => $this->is_enabled,
            'cooldown_minutes' => $this->cooldown_minutes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
