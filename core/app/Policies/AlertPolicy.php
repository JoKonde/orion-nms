<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Alert;
use App\Models\User;

class AlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ALERTS_VIEW->value);
    }

    public function view(User $user, Alert $alert): bool
    {
        return $user->can(PermissionName::ALERTS_VIEW->value);
    }

    public function manage(User $user, Alert $alert): bool
    {
        return $user->can(PermissionName::ALERTS_MANAGE->value);
    }
}
