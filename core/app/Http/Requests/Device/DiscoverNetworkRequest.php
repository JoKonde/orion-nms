<?php

namespace App\Http\Requests\Device;

use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;

class DiscoverNetworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::DEVICES_CREATE->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // CIDR saisi manuellement (ex: 192.168.1.0/24). Si absent : .env ou auto-detect.
            'subnet' => ['sometimes', 'string', 'max:50', 'regex:/^\d{1,3}(\.\d{1,3}){3}\/\d{1,2}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subnet.regex' => 'Format attendu : CIDR (ex: 192.168.1.0/24).',
        ];
    }
}
