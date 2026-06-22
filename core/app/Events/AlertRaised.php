<?php

namespace App\Events;

use App\Http\Resources\AlertResource;
use App\Models\Alert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * AlertRaised — declenche quand une nouvelle alerte est creee.
 *
 * Broadcast Reverb : canal private-org.alerts (dashboard React).
 */
class AlertRaised implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Alert $alert)
    {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('org.alerts')];
    }

    public function broadcastAs(): string
    {
        return 'alert.raised';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->alert->loadMissing(['device', 'rule']);

        return [
            'alert' => (new AlertResource($this->alert))->resolve(),
        ];
    }
}
