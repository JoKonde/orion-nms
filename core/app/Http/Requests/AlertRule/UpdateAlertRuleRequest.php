<?php

namespace App\Http\Requests\AlertRule;

use App\Enums\AlertOperator;
use App\Enums\AlertRuleType;
use App\Enums\AlertSeverity;
use App\Enums\MetricType;
use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlertRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::ALERTS_MANAGE->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rule_type' => ['sometimes', Rule::in(AlertRuleType::values())],
            'metric_type' => ['nullable', Rule::in(MetricType::values())],
            'operator' => ['nullable', Rule::in(AlertOperator::values())],
            'threshold' => ['nullable', 'numeric'],
            'severity' => ['sometimes', Rule::in(AlertSeverity::values())],
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
            'is_enabled' => ['sometimes', 'boolean'],
            'cooldown_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
        ];
    }
}
