<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Agent;
use App\Models\User;

class AgentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::AGENTS_VIEW->value);
    }

    public function view(User $user, Agent $agent): bool
    {
        return $user->can(PermissionName::AGENTS_VIEW->value);
    }

    public function delete(User $user, Agent $agent): bool
    {
        return $user->can(PermissionName::AGENTS_DELETE->value);
    }
}
