<?php

namespace App\Services\Monitoring;

use Symfony\Component\Process\Process;

/**
 * PingService — verification ICMP de disponibilite d'un equipement.
 *
 * Utilise la commande systeme ping (Windows/Linux) via Symfony Process.
 * Pas de blocage du scheduler : PingDeviceJob appelle ce service en async (queue).
 */
class PingService
{
    public function isReachable(string $ipAddress): bool
    {
        $timeoutMs = config('orion.monitoring.ping_timeout_ms', 2000);
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        $command = $isWindows
            ? ['ping', '-n', '1', '-w', (string) $timeoutMs, $ipAddress]
            : ['ping', '-c', '1', '-W', (string) max(1, (int) ceil($timeoutMs / 1000)), $ipAddress];

        $process = new Process($command);
        $process->setTimeout(max(3, (int) ceil($timeoutMs / 1000) + 2));
        $process->run();

        return $process->isSuccessful();
    }
}
