<?php

namespace App\Services;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * AlertService — consultation et cycle de vie des alertes (acquitter, resoudre).
 */
class AlertService
{
    /**
     * @param  array{status?: string, severity?: string, device_id?: int}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->applyFilters(
            Alert::query()->with(['device', 'rule', 'acknowledgedByUser', 'resolvedByUser']),
            $filters
        )
            ->latest('raised_at')
            ->paginate($perPage);
    }

    public function acknowledge(Alert $alert, User $user): Alert
    {
        if ($alert->status === AlertStatus::RESOLVED) {
            abort(422, 'Impossible d\'acquitter une alerte deja resolue.');
        }

        $alert->update([
            'status' => AlertStatus::ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);

        return $alert->fresh(['device', 'rule', 'acknowledgedByUser']);
    }

    public function resolve(Alert $alert, User $user): Alert
    {
        $alert->update([
            'status' => AlertStatus::RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $user->id,
        ]);

        return $alert->fresh(['device', 'rule', 'resolvedByUser']);
    }

    /**
     * @param  Builder<Alert>  $query
     * @param  array{status?: string, severity?: string, device_id?: int}  $filters
     * @return Builder<Alert>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (! empty($filters['device_id'])) {
            $query->where('device_id', $filters['device_id']);
        }

        return $query;
    }
}
