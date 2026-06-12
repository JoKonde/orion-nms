<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Incident */
class IncidentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'device_id' => $this->device_id,
            'alert_id' => $this->alert_id,
            'created_by' => $this->created_by,
            'assigned_to' => $this->assigned_to,
            'opened_at' => $this->opened_at,
            'assigned_at' => $this->assigned_at,
            'started_at' => $this->started_at,
            'resolved_at' => $this->resolved_at,
            'closed_at' => $this->closed_at,
            'resolution_notes' => $this->resolution_notes,
            'device' => $this->whenLoaded('device', fn () => new DeviceResource($this->device)),
            'alert' => $this->whenLoaded('alert', fn () => new AlertResource($this->alert)),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id' => $this->assignee?->id,
                'name' => $this->assignee?->name,
            ]),
            'resolver' => $this->whenLoaded('resolver', fn () => [
                'id' => $this->resolver?->id,
                'name' => $this->resolver?->name,
            ]),
            'closer' => $this->whenLoaded('closer', fn () => [
                'id' => $this->closer?->id,
                'name' => $this->closer?->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
