<?php

namespace App\Services;

use App\Data\AgentRegisterData;
use App\Enums\AgentStatus;
use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\DiscoveryMethod;
use App\Models\Agent;
use App\Models\Device;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * AgentRegistrationService — logique d'auto-enregistrement des agents ORION.
 *
 * Flux :
 *   1. Verifier la cle bootstrap (securite premiere connexion)
 *   2. Reutiliser le device existant (meme IP, ex. decouverte Nmap) ou en creer un
 *   3. Generer un UUID agent + une cle API (retournee UNE SEULE FOIS en clair)
 *   4. Stocker uniquement le hash de la cle API en base
 */
class AgentRegistrationService
{
    /**
     * @return array{agent: Agent, api_key: string}
     *
     * @throws AuthenticationException
     */
    public function register(AgentRegisterData $data, string $bootstrapKey): array
    {
        if (! hash_equals(config('orion.agent.bootstrap_key'), $bootstrapKey)) {
            throw new AuthenticationException('Cle bootstrap agent invalide.');
        }

        return DB::transaction(function () use ($data) {
            $agentUuid = $data->agent_uuid ?? (string) Str::uuid();

            $existingAgent = Agent::where('agent_uuid', $agentUuid)->first();
            if ($existingAgent) {
                return $this->refreshExistingAgent($existingAgent, $data);
            }

            $device = $this->resolveOrUpdateDevice($data);
            $existingOnDevice = $device->agent;

            if ($existingOnDevice && $existingOnDevice->agent_uuid !== $agentUuid) {
                $existingOnDevice->delete();
            }

            $apiKey = 'orion_'.Str::random(48);
            $agentPayload = [
                'device_id' => $device->id,
                'hostname' => $data->hostname,
                'os' => $data->os,
                'os_version' => $data->os_version,
                'architecture' => $data->architecture,
                'agent_version' => $data->agent_version,
                'api_key_hash' => Hash::make($apiKey),
                'status' => AgentStatus::ONLINE,
                'last_seen_at' => now(),
            ];

            if ($existingOnDevice && $existingOnDevice->agent_uuid === $agentUuid) {
                $existingOnDevice->update($agentPayload);

                return [
                    'agent' => $existingOnDevice->fresh()->load('device'),
                    'api_key' => $apiKey,
                ];
            }

            $agent = Agent::create(array_merge($agentPayload, [
                'agent_uuid' => $agentUuid,
                'registered_at' => now(),
            ]));

            return [
                'agent' => $agent->load('device'),
                'api_key' => $apiKey,
            ];
        });
    }

    /**
     * Re-enregistrement avec le meme UUID (reinstallation agent, rotation cle API).
     *
     * @return array{agent: Agent, api_key: string}
     */
    private function refreshExistingAgent(Agent $agent, AgentRegisterData $data): array
    {
        $device = $this->resolveOrUpdateDevice($data);

        if ($agent->device_id !== $device->id) {
            $agent->update(['device_id' => $device->id]);
        }

        $apiKey = 'orion_'.Str::random(48);

        $agent->update([
            'hostname' => $data->hostname,
            'os' => $data->os,
            'os_version' => $data->os_version,
            'architecture' => $data->architecture,
            'agent_version' => $data->agent_version,
            'api_key_hash' => Hash::make($apiKey),
            'status' => AgentStatus::ONLINE,
            'last_seen_at' => now(),
        ]);

        return [
            'agent' => $agent->fresh()->load('device'),
            'api_key' => $apiKey,
        ];
    }

    private function resolveOrUpdateDevice(AgentRegisterData $data): Device
    {
        $now = now();
        $payload = [
            'name' => $data->hostname,
            'mac_address' => $data->mac_address,
            'type' => DeviceType::PC,
            'status' => DeviceStatus::ONLINE,
            'discovery_method' => DiscoveryMethod::AGENT,
            'last_seen_at' => $now,
        ];

        $device = Device::where('ip_address', $data->ip_address)->first();

        if ($device) {
            $device->update($payload);

            return $device->fresh();
        }

        return Device::create(array_merge($payload, [
            'ip_address' => $data->ip_address,
        ]));
    }
}
