<?php

namespace App\Http\Requests\Incident;

use App\Enums\IncidentPriority;
use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::INCIDENTS_UPDATE->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['sometimes', Rule::in(IncidentPriority::values())],
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
        ];
    }
}
