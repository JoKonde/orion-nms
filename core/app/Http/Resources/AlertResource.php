<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Alert */
class AlertResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'alert_rule_id' => $this->alert_rule_id,
            'device_id' => $this->device_id,
            'severity' => $this->severity?->value,
            'status' => $this->status?->value,
            'title' => $this->title,
            'message' => $this->message,
            'metric_type' => $this->metric_type?->value,
            'metric_value' => $this->metric_value,
            'raised_at' => $this->raised_at,
            'acknowledged_at' => $this->acknowledged_at,
            'acknowledged_by' => $this->whenLoaded('acknowledgedByUser', fn () => [
                'id' => $this->acknowledgedByUser?->id,
                'name' => $this->acknowledgedByUser?->name,
            ]),
            'resolved_at' => $this->resolved_at,
            'resolved_by' => $this->whenLoaded('resolvedByUser', fn () => [
                'id' => $this->resolvedByUser?->id,
                'name' => $this->resolvedByUser?->name,
            ]),
            'device' => $this->whenLoaded('device', fn () => new DeviceResource($this->device)),
            'rule' => $this->whenLoaded('rule', fn () => new AlertRuleResource($this->rule)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
