<?php

namespace App\Http\Requests\Incident;

use App\Enums\IncidentPriority;
use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::INCIDENTS_CREATE->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(IncidentPriority::values())],
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
            'alert_id' => ['nullable', 'integer', 'exists:alerts,id', 'unique:incidents,alert_id'],
        ];
    }
}
