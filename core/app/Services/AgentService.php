<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * AgentService — consultation des agents pour le dashboard admin.
 */
class AgentService
{
    /**
     * @param  array{status?: string, search?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Agent::query()->with('device');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('hostname', 'like', "%{$search}%")
                    ->orWhere('agent_uuid', 'like', "%{$search}%");
            });
        }

        return $query->latest('last_seen_at')->paginate($perPage);
    }
}
