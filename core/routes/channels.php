<?php

use App\Enums\PermissionName;
use App\Models\Device;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels — ORION (Module 09 Reverb)
|--------------------------------------------------------------------------
|
| Canaux prives pour le dashboard React (Laravel Echo + Sanctum).
| Cote client : private-org.alerts, private-device.{id}.metrics, etc.
|
*/

Broadcast::channel('org.alerts', function ($user) {
    return $user->can(PermissionName::ALERTS_VIEW->value);
});

Broadcast::channel('org.incidents', function ($user) {
    return $user->can(PermissionName::INCIDENTS_VIEW->value);
});

Broadcast::channel('org.devices', function ($user) {
    return $user->can(PermissionName::DEVICES_VIEW->value);
});

Broadcast::channel('org.agents', function ($user) {
    return $user->can(PermissionName::AGENTS_VIEW->value);
});

Broadcast::channel('org.topology', function ($user) {
    return $user->can(PermissionName::TOPOLOGY_VIEW->value);
});

Broadcast::channel('org.ai', function ($user) {
    return $user->can(PermissionName::AI_USE->value);
});

Broadcast::channel('device.{deviceId}.metrics', function ($user, int $deviceId) {
    if (! $user->can(PermissionName::DEVICES_VIEW->value)) {
        return false;
    }

    return Device::query()->whereKey($deviceId)->exists();
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
