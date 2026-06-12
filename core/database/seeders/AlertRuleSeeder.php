<?php

namespace Database\Seeders;

use App\Enums\AlertOperator;
use App\Enums\AlertRuleType;
use App\Enums\AlertSeverity;
use App\Enums\MetricType;
use App\Models\AlertRule;
use Illuminate\Database\Seeder;

/**
 * Regles d'alerte par defaut ORION (Module 06).
 */
class AlertRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'CPU eleve',
                'description' => 'Alerte si utilisation CPU superieure a 90%',
                'rule_type' => AlertRuleType::METRIC_THRESHOLD,
                'metric_type' => MetricType::CPU,
                'operator' => AlertOperator::GT,
                'threshold' => 90,
                'severity' => AlertSeverity::CRITICAL,
            ],
            [
                'name' => 'RAM elevee',
                'description' => 'Alerte si utilisation RAM superieure a 90%',
                'rule_type' => AlertRuleType::METRIC_THRESHOLD,
                'metric_type' => MetricType::RAM,
                'operator' => AlertOperator::GT,
                'threshold' => 90,
                'severity' => AlertSeverity::WARNING,
            ],
            [
                'name' => 'Disque plein',
                'description' => 'Alerte si utilisation disque superieure a 95%',
                'rule_type' => AlertRuleType::METRIC_THRESHOLD,
                'metric_type' => MetricType::DISK,
                'operator' => AlertOperator::GT,
                'threshold' => 95,
                'severity' => AlertSeverity::CRITICAL,
            ],
            [
                'name' => 'Temperature critique',
                'description' => 'Alerte si temperature superieure a 80°C',
                'rule_type' => AlertRuleType::METRIC_THRESHOLD,
                'metric_type' => MetricType::TEMPERATURE,
                'operator' => AlertOperator::GT,
                'threshold' => 80,
                'severity' => AlertSeverity::CRITICAL,
            ],
            [
                'name' => 'Equipement hors ligne',
                'description' => 'Alerte quand un device passe offline (ping, SNMP ou agent)',
                'rule_type' => AlertRuleType::DEVICE_OFFLINE,
                'metric_type' => null,
                'operator' => null,
                'threshold' => null,
                'severity' => AlertSeverity::CRITICAL,
            ],
        ];

        foreach ($rules as $rule) {
            AlertRule::updateOrCreate(
                ['name' => $rule['name']],
                array_merge($rule, [
                    'rule_type' => $rule['rule_type']->value,
                    'metric_type' => $rule['metric_type']?->value,
                    'operator' => $rule['operator']?->value,
                    'severity' => $rule['severity']->value,
                    'is_enabled' => true,
                    'cooldown_minutes' => 15,
                ])
            );
        }
    }
}
