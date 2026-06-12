<?php

namespace App\Policies;

use App\Enums\IncidentStatus;
use App\Enums\PermissionName;
use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::INCIDENTS_VIEW->value);
    }

    public function view(User $user, Incident $incident): bool
    {
        return $user->can(PermissionName::INCIDENTS_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::INCIDENTS_CREATE->value);
    }

    public function update(User $user, Incident $incident): bool
    {
        return $user->can(PermissionName::INCIDENTS_UPDATE->value)
            && $incident->status !== IncidentStatus::CLOSED;
    }

    public function assign(User $user, Incident $incident): bool
    {
        return $user->can(PermissionName::INCIDENTS_ASSIGN->value)
            && $incident->status !== IncidentStatus::CLOSED;
    }

    public function close(User $user, Incident $incident): bool
    {
        return $user->can(PermissionName::INCIDENTS_CLOSE->value)
            && $incident->status === IncidentStatus::RESOLVED;
    }

    public function delete(User $user, Incident $incident): bool
    {
        return $user->can(PermissionName::INCIDENTS_UPDATE->value)
            && $incident->status === IncidentStatus::CLOSED;
    }
}
