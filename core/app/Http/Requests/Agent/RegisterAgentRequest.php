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
            'ip_address' => ['required', 'ip', 'not_in:127.0.0.1,0.0.0.0'],
            'mac_address' => ['nullable', 'string', 'max:17'],
            'agent_uuid' => ['nullable', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ip_address.not_in' => 'L\'adresse IP 127.0.0.1 n\'est pas acceptee. Utilisez l\'IP LAN du poste.',
        ];
    }
}
