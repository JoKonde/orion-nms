<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\AlertRule;
use App\Models\User;

class AlertRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ALERTS_VIEW->value);
    }

    public function view(User $user, AlertRule $alertRule): bool
    {
        return $user->can(PermissionName::ALERTS_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::ALERTS_MANAGE->value);
    }

    public function update(User $user, AlertRule $alertRule): bool
    {
        return $user->can(PermissionName::ALERTS_MANAGE->value);
    }

    public function delete(User $user, AlertRule $alertRule): bool
    {
        return $user->can(PermissionName::ALERTS_MANAGE->value);
    }
}
