<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Device;
use App\Models\User;

/**
 * DevicePolicy — autorisation fine sur les equipements.
 *
 * Utilisee par $this->authorize() ou @can dans les vues.
 * Complement des middlewares permission: sur les routes.
 */
class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::DEVICES_VIEW->value);
    }

    public function view(User $user, Device $device): bool
    {
        return $user->can(PermissionName::DEVICES_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::DEVICES_CREATE->value);
    }

    public function update(User $user, Device $device): bool
    {
        return $user->can(PermissionName::DEVICES_UPDATE->value);
    }

    public function delete(User $user, Device $device): bool
    {
        return $user->can(PermissionName::DEVICES_DELETE->value);
    }

    /**
     * Consultation cartographie reseau (Module 08).
     */
    public function viewTopology(User $user): bool
    {
        return $user->can(PermissionName::TOPOLOGY_VIEW->value);
    }
}
