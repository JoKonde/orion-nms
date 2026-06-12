<?php

namespace App\Http\Requests\Device;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\DiscoveryMethod;
use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip', 'unique:devices,ip_address'],
            'mac_address' => ['nullable', 'string', 'max:17', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
            'type' => ['required', Rule::in(DeviceType::values())],
            'vendor' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'firmware' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(DeviceStatus::values())],
            'discovery_method' => ['sometimes', Rule::in(DiscoveryMethod::values())],
            'uptime_seconds' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => "Le nom de l'equipement est obligatoire.",
            'ip_address.required' => "L'adresse IP est obligatoire.",
            'ip_address.unique' => 'Cette adresse IP est deja enregistree.',
            'type.required' => 'Le type d\'equipement est obligatoire.',
            'mac_address.regex' => 'Le format MAC doit etre AA:BB:CC:DD:EE:FF ou AA-BB-CC-DD-EE-FF.',
        ];
    }
}
