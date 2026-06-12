<?php

namespace App\Services;

use App\Enums\AlertOperator;
use App\Enums\AlertRuleType;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\DeviceStatus;
use App\Enums\MetricType;
use App\Events\AlertRaised;
use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\Device;
use Illuminate\Support\Collection;

/**
 * AlertEvaluator — moteur d'evaluation des regles d'alerte ORION.
 *
 * Appele apres chaque ingestion metrique (Listener MetricReceived)
 * et quand un device passe offline (Event DeviceWentOffline).
 *
 * POURQUOI un service dedie ?
 * -----------------------------
 * La logique de seuils (CPU>90%, RAM>90%...) ne doit pas etre dans
 * MetricIngestionService ni dans les controllers. Un seul endroit
 * pour tester, ajuster le cooldown et auto-resoudre les alertes.
 */
class AlertEvaluator
{
    /**
     * Evalue les metriques fraichement ingerees pour un device.
     *
     * @param  array<int, array{type: string, value: float}>  $points
     */
    public function evaluateMetrics(int $deviceId, array $points): void
    {
        if ($points === []) {
            return;
        }

        $rules = $this->enabledRulesForDevice($deviceId, AlertRuleType::METRIC_THRESHOLD);

        foreach ($rules as $rule) {
            $point = $this->findPoint($points, $rule->metric_type);

            if ($point === null) {
                continue;
            }

            $value = (float) $point['value'];
            $breached = $this->isThresholdBreached($value, $rule->operator, (float) $rule->threshold);

            if ($breached) {
                $this->raiseIfNeeded($rule, $deviceId, $value, $point['type']);
            } else {
                $this->autoResolveActiveAlerts($rule, $deviceId);
            }
        }
    }

    /**
     * Evalue les regles "device offline" quand le statut passe a offline.
     */
    public function evaluateDeviceOffline(Device $device): void
    {
        if ($device->status !== DeviceStatus::OFFLINE) {
            return;
        }

        $rules = $this->enabledRulesForDevice($device->id, AlertRuleType::DEVICE_OFFLINE);

        foreach ($rules as $rule) {
            $this->raiseIfNeeded(
                $rule,
                $device->id,
                null,
                null,
                "Equipement {$device->name} ({$device->ip_address}) est hors ligne.",
            );
        }
    }

    /**
     * Auto-resout les alertes offline quand le device revient online.
     */
    public function evaluateDeviceOnline(Device $device): void
    {
        if ($device->status !== DeviceStatus::ONLINE) {
            return;
        }

        $rules = AlertRule::query()
            ->where('rule_type', AlertRuleType::DEVICE_OFFLINE)
            ->where('is_enabled', true)
            ->where(function ($q) use ($device) {
                $q->whereNull('device_id')->orWhere('device_id', $device->id);
            })
            ->get();

        foreach ($rules as $rule) {
            $this->autoResolveActiveAlerts($rule, $device->id);
        }
    }

    /**
     * @return Collection<int, AlertRule>
     */
    private function enabledRulesForDevice(int $deviceId, AlertRuleType $type): Collection
    {
        return AlertRule::query()
            ->where('rule_type', $type)
            ->where('is_enabled', true)
            ->where(function ($q) use ($deviceId) {
                $q->whereNull('device_id')->orWhere('device_id', $deviceId);
            })
            ->get();
    }

    /**
     * @param  array<int, array{type: string, value: float}>  $points
     * @return array{type: string, value: float}|null
     */
    private function findPoint(array $points, ?MetricType $metricType): ?array
    {
        if ($metricType === null) {
            return null;
        }

        foreach ($points as $point) {
            if ($point['type'] === $metricType->value) {
                return $point;
            }
        }

        return null;
    }

    private function isThresholdBreached(float $value, ?AlertOperator $operator, float $threshold): bool
    {
        return match ($operator) {
            AlertOperator::GT => $value > $threshold,
            AlertOperator::GTE => $value >= $threshold,
            AlertOperator::LT => $value < $threshold,
            AlertOperator::LTE => $value <= $threshold,
            default => false,
        };
    }

    private function raiseIfNeeded(
        AlertRule $rule,
        int $deviceId,
        ?float $metricValue,
        ?string $metricType,
        ?string $customMessage = null,
    ): void {
        if ($this->hasActiveAlert($rule, $deviceId)) {
            return;
        }

        if ($this->isInCooldown($rule, $deviceId)) {
            return;
        }

        $device = Device::find($deviceId);
        if (! $device) {
            return;
        }

        $title = $this->buildTitle($rule, $device);
        $message = $customMessage ?? $this->buildMessage($rule, $device, $metricValue);

        $alert = Alert::create([
            'alert_rule_id' => $rule->id,
            'device_id' => $deviceId,
            'severity' => $rule->severity->value,
            'status' => AlertStatus::RAISED->value,
            'title' => $title,
            'message' => $message,
            'metric_type' => $metricType,
            'metric_value' => $metricValue,
            'raised_at' => now(),
        ]);

        AlertRaised::dispatch($alert);
    }

    private function hasActiveAlert(AlertRule $rule, int $deviceId): bool
    {
        return Alert::query()
            ->where('alert_rule_id', $rule->id)
            ->where('device_id', $deviceId)
            ->whereIn('status', AlertStatus::activeValues())
            ->exists();
    }

    private function isInCooldown(AlertRule $rule, int $deviceId): bool
    {
        $lastResolved = Alert::query()
            ->where('alert_rule_id', $rule->id)
            ->where('device_id', $deviceId)
            ->where('status', AlertStatus::RESOLVED)
            ->latest('resolved_at')
            ->value('resolved_at');

        if (! $lastResolved) {
            return false;
        }

        return $lastResolved->gt(now()->subMinutes($rule->cooldown_minutes));
    }

    private function autoResolveActiveAlerts(AlertRule $rule, int $deviceId): void
    {
        Alert::query()
            ->where('alert_rule_id', $rule->id)
            ->where('device_id', $deviceId)
            ->whereIn('status', AlertStatus::activeValues())
            ->update([
                'status' => AlertStatus::RESOLVED->value,
                'resolved_at' => now(),
            ]);
    }

    private function buildTitle(AlertRule $rule, Device $device): string
    {
        if ($rule->rule_type === AlertRuleType::DEVICE_OFFLINE) {
            return "[{$rule->severity->value}] {$device->name} hors ligne";
        }

        return "[{$rule->severity->value}] {$rule->name} — {$device->name}";
    }

    private function buildMessage(AlertRule $rule, Device $device, ?float $value): string
    {
        $metric = $rule->metric_type?->value ?? 'metrique';
        $threshold = $rule->threshold;
        $operator = $rule->operator?->value ?? '>';

        return "{$device->name} ({$device->ip_address}) : {$metric}={$value} (seuil {$operator} {$threshold}).";
    }
}
