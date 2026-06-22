<?php

namespace App\Services;

use App\Data\TopologyEdgeData;
use App\Data\TopologyNodeData;
use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\TopologyLinkStatus;
use App\Enums\TopologyLinkType;
use App\Events\TopologyUpdated;
use App\Models\Device;
use App\Models\TopologyLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * TopologyService — construction et export de la cartographie reseau ORION.
 *
 * Sources de liens :
 *   1. nmap_subnet — devices du meme sous-reseau /24 relies au gateway (.1 ou router)
 *   2. lldp         — voisins SNMP LLDP (si snmpwalk disponible)
 *   3. manual       — ajout admin via API (futur dashboard)
 *
 * Export GET /topology : format Cytoscape.js { elements: { nodes, edges } }
 */
class TopologyService
{
    private const LLDP_REM_SYS_NAME_OID = '1.0.8802.1.1.2.1.4.1.1.9';

    private const LLDP_REM_MAN_ADDR_OID = '1.0.8802.1.1.2.1.4.1.1.7';

    /**
     * Reconstruit toute la topologie (subnet + LLDP).
     *
     * @return array{subnet_links: int, lldp_links: int, stale_subnet_links_removed: int}
     */
    public function rebuild(): array
    {
        $subnetLinks = $this->discoverSubnetLinks();
        $lldpLinks = $this->discoverLldpLinks();

        TopologyUpdated::dispatch($this->getGraph(), 'rebuilt');

        return [
            'subnet_links' => $subnetLinks['created'],
            'lldp_links' => $lldpLinks,
            'stale_subnet_links_removed' => $subnetLinks['stale_removed'],
        ];
    }

    /**
     * Lie un device nouvellement decouvert (Nmap) a son gateway subnet.
     */
    public function linkNewDevice(Device $device): void
    {
        $subnet = $this->subnetKey($device->ip_address);
        $peers = Device::query()
            ->where('ip_address', 'like', $subnet['prefix'].'%')
            ->where('id', '!=', $device->id)
            ->get();

        if ($peers->isEmpty()) {
            return;
        }

        $gateway = $this->findGateway($peers->push($device));

        if ($gateway && $gateway->id !== $device->id) {
            $this->upsertLink($gateway, $device, TopologyLinkType::NMAP_SUBNET);
        }
    }

    /**
     * Met a jour le statut des liens quand un device change d'etat.
     */
    public function refreshLinksForDevice(Device $device): void
    {
        TopologyLink::query()
            ->where('source_device_id', $device->id)
            ->orWhere('target_device_id', $device->id)
            ->each(function (TopologyLink $link) {
                $link->update(['link_status' => $this->computeLinkStatus($link)]);
            });

        TopologyUpdated::dispatch($this->getGraph(), 'link_status_updated');
    }

    /**
     * Graphe complet au format Cytoscape.js pour le dashboard React.
     *
     * @return array{elements: array{nodes: array, edges: array}, meta: array<string, mixed>}
     */
    public function getGraph(): array
    {
        $devices = Device::query()->orderBy('name')->get();
        $links = TopologyLink::query()
            ->with(['sourceDevice', 'targetDevice'])
            ->get();

        $nodes = $devices->map(fn (Device $d) => TopologyNodeData::from([
            'id' => (string) $d->id,
            'label' => $d->name,
            'type' => $d->type?->value ?? DeviceType::OTHER->value,
            'status' => $d->status?->value ?? DeviceStatus::UNKNOWN->value,
            'ip' => $d->ip_address,
            'vendor' => $d->vendor,
        ])->toCytoscape())->values()->all();

        $edges = $links->map(function (TopologyLink $link) {
            $status = $this->computeLinkStatus($link);

            return TopologyEdgeData::from([
                'id' => 'link-'.$link->id,
                'source' => (string) $link->source_device_id,
                'target' => (string) $link->target_device_id,
                'status' => $status->value,
                'link_type' => $link->link_type->value,
                'source_interface' => $link->source_interface,
                'target_interface' => $link->target_interface,
            ])->toCytoscape();
        })->values()->all();

        return [
            'elements' => [
                'nodes' => $nodes,
                'edges' => $edges,
            ],
            'meta' => [
                'node_count' => count($nodes),
                'edge_count' => count($edges),
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Liste paginee des liens bruts (debug / admin).
     *
     * @return Collection<int, TopologyLink>
     */
    public function listLinks(): Collection
    {
        return TopologyLink::query()
            ->with(['sourceDevice', 'targetDevice'])
            ->latest()
            ->get();
    }

    /**
     * @return array{prefix: string, cidr: string}
     */
    public function subnetKey(string $ip): array
    {
        $parts = explode('.', $ip);
        $prefix = ($parts[0] ?? '0').'.'.($parts[1] ?? '0').'.'.($parts[2] ?? '0').'.';

        return [
            'prefix' => $prefix,
            'cidr' => $prefix.'0/24',
        ];
    }

    /**
     * @return array{created: int, stale_removed: int}
     */
    private function discoverSubnetLinks(): array
    {
        $staleRemoved = TopologyLink::query()
            ->where('link_type', TopologyLinkType::NMAP_SUBNET->value)
            ->delete();

        $created = 0;
        $groups = Device::all()->groupBy(fn (Device $d) => $this->subnetKey($d->ip_address)['prefix']);

        foreach ($groups as $group) {
            if ($group->count() < 2) {
                continue;
            }

            $gateway = $this->findGateway($group);
            if (! $gateway) {
                continue;
            }

            foreach ($group as $device) {
                if ($device->id === $gateway->id) {
                    continue;
                }

                if ($this->upsertLink($gateway, $device, TopologyLinkType::NMAP_SUBNET)) {
                    $created++;
                }
            }
        }

        return [
            'created' => $created,
            'stale_removed' => $staleRemoved,
        ];
    }

    private function discoverLldpLinks(): int
    {
        if (! $this->isSnmpWalkAvailable()) {
            Log::debug('LLDP discovery skipped — snmpwalk non disponible.');

            return 0;
        }

        $created = 0;
        $community = config('orion.monitoring.snmp_community', 'public');
        $timeout = config('orion.monitoring.snmp_timeout', 3);

        $devices = Device::query()
            ->whereIn('type', [
                DeviceType::ROUTER->value,
                DeviceType::SWITCH->value,
                DeviceType::FIREWALL->value,
            ])
            ->get();

        foreach ($devices as $device) {
            $neighbors = $this->pollLldpNeighbors($device, $community, $timeout);

            foreach ($neighbors as $neighbor) {
                $target = $this->resolveNeighborDevice($neighbor);
                if (! $target || $target->id === $device->id) {
                    continue;
                }

                if ($this->upsertLink(
                    $device,
                    $target,
                    TopologyLinkType::LLDP,
                    $neighbor['local_interface'] ?? null,
                    $neighbor['remote_interface'] ?? null,
                    ['remote_name' => $neighbor['sys_name'] ?? null],
                )) {
                    $created++;
                }
            }
        }

        return $created;
    }

    /**
     * @return array<int, array{sys_name: ?string, ip: ?string, local_interface: ?string, remote_interface: ?string}>
     */
    private function pollLldpNeighbors(Device $device, string $community, int $timeout): array
    {
        $names = $this->snmpWalk($device->ip_address, $community, self::LLDP_REM_SYS_NAME_OID, $timeout);
        $addresses = $this->snmpWalk($device->ip_address, $community, self::LLDP_REM_MAN_ADDR_OID, $timeout);

        $neighbors = [];
        foreach ($names as $index => $sysName) {
            $neighbors[$index] = [
                'sys_name' => $sysName,
                'ip' => $addresses[$index] ?? null,
                'local_interface' => null,
                'remote_interface' => null,
            ];
        }

        return array_values($neighbors);
    }

    /**
     * @return array<int, string>
     */
    private function snmpWalk(string $ip, string $community, string $oid, int $timeout): array
    {
        $process = new Process([
            'snmpwalk', '-v2c', '-c', $community, '-t', (string) $timeout, $ip, $oid,
        ]);
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        $values = [];
        foreach (explode("\n", trim($process->getOutput())) as $line) {
            if (preg_match('/=\s(?:STRING|Hex-STRING|IpAddress):\s"?([^"\s]+)"?/', $line, $m)) {
                $values[] = trim($m[1], '"');
            }
        }

        return $values;
    }

    /**
     * @param  array{sys_name: ?string, ip: ?string}  $neighbor
     */
    private function resolveNeighborDevice(array $neighbor): ?Device
    {
        if (! empty($neighbor['ip'])) {
            $byIp = Device::where('ip_address', $neighbor['ip'])->first();
            if ($byIp) {
                return $byIp;
            }
        }

        if (! empty($neighbor['sys_name'])) {
            return Device::query()
                ->where('name', 'like', '%'.$neighbor['sys_name'].'%')
                ->first();
        }

        return null;
    }

    /**
     * @param  Collection<int, Device>  $group
     */
    private function findGateway(Collection $group): ?Device
    {
        $gateway = $group->first(fn (Device $d) => str_ends_with($d->ip_address, '.1'));
        if ($gateway) {
            return $gateway;
        }

        $router = $group->first(fn (Device $d) => $d->type === DeviceType::ROUTER);
        if ($router) {
            return $router;
        }

        return $group->sortBy('ip_address')->first();
    }

    private function upsertLink(
        Device $source,
        Device $target,
        TopologyLinkType $type,
        ?string $sourceInterface = null,
        ?string $targetInterface = null,
        array $metadata = [],
    ): bool {
        if ($type === TopologyLinkType::NMAP_SUBNET) {
            // Fleches Cytoscape : gateway -> peripherie (pas de tri par ID).
            $srcId = $source->id;
            $tgtId = $target->id;
        } else {
            [$srcId, $tgtId] = $this->canonicalPair($source->id, $target->id);
        }

        $link = TopologyLink::query()->firstOrNew([
            'source_device_id' => $srcId,
            'target_device_id' => $tgtId,
            'link_type' => $type->value,
        ]);

        $wasNew = ! $link->exists;

        $link->fill([
            'link_status' => $this->computeLinkStatusFromDevices(
                Device::find($srcId),
                Device::find($tgtId),
            )->value,
            'source_interface' => $sourceInterface,
            'target_interface' => $targetInterface,
            'metadata' => array_merge($link->metadata ?? [], $metadata),
        ]);
        $link->save();

        return $wasNew;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function canonicalPair(int $a, int $b): array
    {
        return $a < $b ? [$a, $b] : [$b, $a];
    }

    private function computeLinkStatus(TopologyLink $link): TopologyLinkStatus
    {
        return $this->computeLinkStatusFromDevices($link->sourceDevice, $link->targetDevice);
    }

    private function computeLinkStatusFromDevices(?Device $a, ?Device $b): TopologyLinkStatus
    {
        if (! $a || ! $b) {
            return TopologyLinkStatus::UNKNOWN;
        }

        if ($a->status === DeviceStatus::ONLINE && $b->status === DeviceStatus::ONLINE) {
            return TopologyLinkStatus::UP;
        }

        if ($a->status === DeviceStatus::OFFLINE || $b->status === DeviceStatus::OFFLINE) {
            return TopologyLinkStatus::DOWN;
        }

        return TopologyLinkStatus::UNKNOWN;
    }

    private function isSnmpWalkAvailable(): bool
    {
        $process = new Process(['snmpwalk', '-V']);
        $process->run();

        return $process->isSuccessful();
    }
}
