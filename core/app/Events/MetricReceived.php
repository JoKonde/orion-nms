<?php

namespace App\Events;

use App\Models\Agent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * MetricReceived — broadcast resume metriques sur canal device (dashboard graphiques live).
 */
class MetricReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Agent $agent,
        public int $deviceId,
        public int $pointsCount,
        /** @var array<int, array{type: string, value: float}> */
        public array $points = [],
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('device.'.$this->deviceId.'.metrics')];
    }

    public function broadcastAs(): string
    {
        return 'metric.received';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'device_id' => $this->deviceId,
            'agent_id' => $this->agent->id,
            'points_count' => $this->pointsCount,
            'points' => $this->points,
            'received_at' => now()->toIso8601String(),
        ];
    }
}
