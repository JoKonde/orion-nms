<?php

namespace App\Http\Requests\Metric;

use App\Enums\MetricType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMetricsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'agent_uuid' => ['required', 'uuid'],
            'batch' => ['required', 'array', 'min:1', 'max:1000'],
            'batch.*.type' => ['required', Rule::in(MetricType::values())],
            'batch.*.value' => ['required', 'numeric'],
            'batch.*.recorded_at' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'batch.required' => 'Le batch de metriques est obligatoire.',
            'batch.max' => 'Maximum 1000 points par requete.',
        ];
    }
}
