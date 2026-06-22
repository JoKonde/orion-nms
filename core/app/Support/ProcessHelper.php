<?php

namespace App\Support;

use Symfony\Component\Process\Process;

/**
 * Lance des sous-processus avec un dossier TEMP writable (fix Windows + php artisan serve).
 *
 * Evite : fopen(C:\WINDOWS\sf_proc_00.out.lock): Permission denied
 */
class ProcessHelper
{
    public static function tempDirectory(): string
    {
        $dir = storage_path('framework/orion-tmp');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * Variables d'environnement pour Symfony Process (TEMP/TMP vers storage).
     *
     * @return array<string, string>
     */
    public static function environment(): array
    {
        $temp = self::tempDirectory();

        $base = array_filter(
            $_ENV + $_SERVER,
            static fn ($value) => is_string($value) || is_numeric($value)
        );

        /** @var array<string, string> $env */
        $env = array_map(static fn ($value) => (string) $value, $base);

        $env['TEMP'] = $temp;
        $env['TMP'] = $temp;
        $env['TMPDIR'] = $temp;

        return $env;
    }

    /**
     * @param  array<int, string>  $command
     */
    public static function make(
        array $command,
        ?string $cwd = null,
        ?int $timeout = 300,
    ): Process {
        $process = new Process(
            $command,
            $cwd ?? base_path(),
            self::environment(),
            null,
            $timeout,
        );

        return $process;
    }
}
