<?php

namespace App\Events;

use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceDiscovered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Device $device)
    {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('org.devices')];
    }

    public function broadcastAs(): string
    {
        return 'device.discovered';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'device' => (new DeviceResource($this->device))->resolve(),
        ];
    }
}
