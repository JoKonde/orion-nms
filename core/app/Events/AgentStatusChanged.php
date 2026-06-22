<?php

namespace App\Events;

use App\Models\Agent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * AgentStatusChanged — statut agent online/offline (Module 09 Reverb).
 */
class AgentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Agent $agent,
        public string $previousStatus,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('org.agents')];
    }

    public function broadcastAs(): string
    {
        return 'agent.status.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->agent->loadMissing('device');

        return [
            'agent' => [
                'id' => $this->agent->id,
                'name' => $this->agent->name,
                'status' => $this->agent->status->value,
                'previous_status' => $this->previousStatus,
                'device_id' => $this->agent->device_id,
                'last_seen_at' => $this->agent->last_seen_at,
            ],
        ];
    }
}
