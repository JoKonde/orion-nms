<?php

namespace App\Services;

use App\Data\DeviceData;
use App\Enums\DeviceStatus;
use App\Enums\DiscoveryMethod;
use App\Models\Device;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * DeviceService — logique metier du referentiel equipements (Module 02).
 *
 * Centralise creation, mise a jour, suppression et listes filtrees.
 * Les controllers et les futurs Jobs (ping, Nmap) appellent ce service.
 */
class DeviceService
{
    /**
     * Liste paginee avec filtres optionnels (type, statut, recherche texte).
     *
     * @param  array{type?: string, status?: string, search?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->applyFilters(Device::query(), $filters)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Cree un equipement a partir d'un DTO DeviceData.
     *
     * DeviceData::from() convertit un tableau valide en objet type ;
     * toArray() le retransforme pour Eloquent::create().
     */
    public function create(DeviceData $data): Device
    {
        $attributes = $data->toArray();

        // Valeurs par defaut si non fournies dans le DTO.
        $attributes['status'] = $data->status?->value ?? DeviceStatus::UNKNOWN->value;
        $attributes['discovery_method'] = $data->discovery_method?->value ?? DiscoveryMethod::MANUAL->value;

        return Device::create($attributes);
    }

    /**
     * Met a jour un equipement (champs partiels acceptes via tableau).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Device $device, array $data): Device
    {
        $device->fill(array_filter([
            'name' => $data['name'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'mac_address' => $data['mac_address'] ?? null,
            'type' => $data['type'] ?? null,
            'vendor' => $data['vendor'] ?? null,
            'model' => $data['model'] ?? null,
            'firmware' => $data['firmware'] ?? null,
            'status' => $data['status'] ?? null,
            'discovery_method' => $data['discovery_method'] ?? null,
            'uptime_seconds' => $data['uptime_seconds'] ?? null,
            'description' => $data['description'] ?? null,
        ], fn ($value) => ! is_null($value)));

        $device->save();

        return $device->fresh();
    }

    public function delete(Device $device): void
    {
        $device->delete();
    }

    /**
     * Applique les filtres de recherche sur la requete devices.
     *
     * @param  Builder<Device>  $query
     * @param  array{type?: string, status?: string, search?: string}  $filters
     * @return Builder<Device>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
