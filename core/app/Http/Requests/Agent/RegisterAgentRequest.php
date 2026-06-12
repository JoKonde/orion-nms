<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class RegisterAgentRequest extends FormRequest
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
            'hostname' => ['required', 'string', 'max:255'],
            'os' => ['required', 'in:windows,linux'],
            'os_version' => ['nullable', 'string', 'max:255'],
            'architecture' => ['nullable', 'string', 'max:50'],
            'agent_version' => ['nullable', 'string', 'max:50'],
            'ip_address' => ['required', 'ip', 'unique:devices,ip_address'],
            'mac_address' => ['nullable', 'string', 'max:17'],
            'agent_uuid' => ['nullable', 'uuid'],
        ];
    }
}
