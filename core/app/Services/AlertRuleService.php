<?php

namespace App\Services;

use App\Data\AlertRuleData;
use App\Enums\AlertRuleType;
use App\Models\AlertRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * AlertRuleService — CRUD des regles d'alerte (seuils CPU, RAM, offline...).
 */
class AlertRuleService
{
    /**
     * @param  array{rule_type?: string, is_enabled?: bool, device_id?: int}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->applyFilters(AlertRule::query()->with('device'), $filters)
            ->latest()
            ->paginate($perPage);
    }

    public function create(AlertRuleData $data): AlertRule
    {
        $this->validateRuleConsistency($data);

        return AlertRule::create($this->toAttributes($data));
    }

    public function update(AlertRule $rule, AlertRuleData $data): AlertRule
    {
        $this->validateRuleConsistency($data);
        $rule->update($this->toAttributes($data));

        return $rule->fresh(['device']);
    }

    public function delete(AlertRule $rule): void
    {
        $rule->delete();
    }

    private function validateRuleConsistency(AlertRuleData $data): void
    {
        if ($data->rule_type === AlertRuleType::METRIC_THRESHOLD) {
            if ($data->metric_type === null || $data->operator === null || $data->threshold === null) {
                throw ValidationException::withMessages([
                    'rule_type' => 'Une regle metric_threshold requiert metric_type, operator et threshold.',
                ]);
            }
        }

        if ($data->rule_type === AlertRuleType::DEVICE_OFFLINE) {
            if ($data->metric_type !== null || $data->operator !== null || $data->threshold !== null) {
                throw ValidationException::withMessages([
                    'rule_type' => 'Une regle device_offline ne doit pas avoir metric_type, operator ou threshold.',
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function toAttributes(AlertRuleData $data): array
    {
        return [
            'name' => $data->name,
            'description' => $data->description,
            'rule_type' => $data->rule_type->value,
            'metric_type' => $data->metric_type?->value,
            'operator' => $data->operator?->value,
            'threshold' => $data->threshold,
            'severity' => $data->severity->value,
            'device_id' => $data->device_id,
            'is_enabled' => $data->is_enabled ?? true,
            'cooldown_minutes' => $data->cooldown_minutes ?? 15,
        ];
    }

    /**
     * @param  Builder<AlertRule>  $query
     * @param  array{rule_type?: string, is_enabled?: bool, device_id?: int}  $filters
     * @return Builder<AlertRule>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['rule_type'])) {
            $query->where('rule_type', $filters['rule_type']);
        }

        if (isset($filters['is_enabled'])) {
            $query->where('is_enabled', filter_var($filters['is_enabled'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['device_id'])) {
            $query->where('device_id', $filters['device_id']);
        }

        return $query;
    }
}
