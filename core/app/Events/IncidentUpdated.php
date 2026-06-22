<?php

namespace App\Events;

use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Incident $incident,
        public string $action,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('org.incidents')];
    }

    public function broadcastAs(): string
    {
        return 'incident.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->incident->loadMissing(['device', 'alert', 'assignee']);

        return [
            'action' => $this->action,
            'incident' => (new IncidentResource($this->incident))->resolve(),
        ];
    }
}
