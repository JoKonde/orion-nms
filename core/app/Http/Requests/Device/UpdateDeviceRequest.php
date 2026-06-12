<?php

namespace App\Http\Requests\Device;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\DiscoveryMethod;
use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::DEVICES_UPDATE->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $deviceId = $this->route('device')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'ip_address' => ['sometimes', 'ip', Rule::unique('devices', 'ip_address')->ignore($deviceId)],
            'mac_address' => ['nullable', 'string', 'max:17', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
            'type' => ['sometimes', Rule::in(DeviceType::values())],
            'vendor' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'firmware' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(DeviceStatus::values())],
            'discovery_method' => ['sometimes', Rule::in(DiscoveryMethod::values())],
            'uptime_seconds' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }
}
