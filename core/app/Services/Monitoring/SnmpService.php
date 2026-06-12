<?php

namespace App\Services\Monitoring;

use App\Models\Device;
use App\Models\DeviceInterface;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * SnmpService — collecte SNMP v2c (uptime, description, interfaces).
 *
 * Utilise snmpget/snmpwalk en ligne de commande si disponibles sur le serveur.
 * En production Linux, installer : snmp + libsnmp-dev (ou php-snmp).
 */
class SnmpService
{
    private const OID_SYS_UPTIME = '1.3.6.1.2.1.1.3.0';

    private const OID_SYS_DESCR = '1.3.6.1.2.1.1.1.0';

    private const OID_SYS_NAME = '1.3.6.1.2.1.1.5.0';

    public function isAvailable(): bool
    {
        $process = new Process(['snmpget', '-V']);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Interroge un equipement et met a jour device + interfaces.
     *
     * @return array{success: bool, message?: string}
     */
    public function poll(Device $device): array
    {
        if (! $this->isAvailable()) {
            return ['success' => false, 'message' => 'snmpget non disponible sur ce serveur.'];
        }

        $community = config('orion.monitoring.snmp_community', 'public');
        $timeout = config('orion.monitoring.snmp_timeout', 3);
        $ip = $device->ip_address;

        $uptimeRaw = $this->get($ip, $community, self::OID_SYS_UPTIME, $timeout);
        if ($uptimeRaw === null) {
            return ['success' => false, 'message' => 'SNMP timeout ou communaute invalide.'];
        }

        $sysDescr = $this->get($ip, $community, self::OID_SYS_DESCR, $timeout);
        $sysName = $this->get($ip, $community, self::OID_SYS_NAME, $timeout);

        // sysUpTime SNMP = centiemes de seconde.
        $uptimeSeconds = (int) floor(((int) preg_replace('/\D/', '', $uptimeRaw)) / 100);

        $device->update([
            'uptime_seconds' => $uptimeSeconds,
            'vendor' => $device->vendor ?? $this->guessVendor($sysDescr),
            'firmware' => $device->firmware ?? $sysDescr,
            'name' => $sysName ?: $device->name,
        ]);

        $this->pollInterfaces($device, $community, $timeout);

        return ['success' => true];
    }

    private function get(string $ip, string $community, string $oid, int $timeout): ?string
    {
        $process = new Process([
            'snmpget', '-v2c', '-c', $community, '-t', (string) $timeout, $ip, $oid,
        ]);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        // Format : "OID = TYPE: value"
        $output = trim($process->getOutput());
        if (preg_match('/:\s"(.*)"\s*$/', $output, $m)) {
            return stripcslashes($m[1]);
        }
        if (preg_match('/:\s(\S+)\s*$/', $output, $m)) {
            return $m[1];
        }

        return $output ?: null;
    }

    private function pollInterfaces(Device $device, string $community, int $timeout): void
    {
        $process = new Process([
            'snmpwalk', '-v2c', '-c', $community, '-t', (string) $timeout, $device->ip_address, 'IF-MIB::ifDescr',
        ]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::debug('SNMP walk interfaces failed', ['device_id' => $device->id]);

            return;
        }

        foreach (explode("\n", trim($process->getOutput())) as $line) {
            if (! preg_match('/\.(\d+)\s=\sSTRING:\s"(.+)"/', $line, $m)) {
                continue;
            }

            DeviceInterface::updateOrCreate(
                ['device_id' => $device->id, 'name' => $m[2]],
                ['oper_status' => 'up']
            );
        }
    }

    private function guessVendor(?string $sysDescr): ?string
    {
        if (! $sysDescr) {
            return null;
        }

        $vendors = ['Cisco', 'HP', 'HPE', 'Juniper', 'Fortinet', 'MikroTik', 'Dell', 'Huawei'];
        foreach ($vendors as $vendor) {
            if (stripos($sysDescr, $vendor) !== false) {
                return $vendor;
            }
        }

        return null;
    }
}
