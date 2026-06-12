<?php

namespace App\Console;

use App\Jobs\AggregateMetricsJob;
use App\Jobs\CheckAgentsOfflineJob;
use App\Jobs\DispatchMonitoringJobs;
use App\Jobs\NmapScanJob;
use App\Services\Monitoring\NetworkDetectionService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        /*
         * POURQUOI le Scheduler Laravel ?
         * -------------------------------
         * Le Scheduler remplace les crons manuels ("* * * * * php script.php").
         * Une seule entree cron systeme suffit : * * * * * php artisan schedule:run
         *
         * Ici, chaque minute on dispatch CheckAgentsOfflineJob dans la file Redis.
         * withoutOverlapping() evite qu'un nouveau Job demarre si le precedent
         * n'est pas fini (utile si tu as beaucoup d'agents a verifier).
         *
         * En dev local : php artisan schedule:work (simule le cron)
         * En prod      : cron + php artisan queue:work (worker Redis)
         */
        $schedule->job(new CheckAgentsOfflineJob)
            ->everyMinute()
            ->withoutOverlapping();

        // Agregation horaire des metriques brutes -> table metrics_hourly (Module 04).
        $schedule->job(new AggregateMetricsJob)
            ->hourly()
            ->withoutOverlapping();

        /*
         * Module 05 — Monitoring sans agent
         * ---------------------------------
         * Chaque minute : dispatch PingDeviceJob pour chaque device sans agent.
         * Toutes les 5 min : idem + PollSnmpJob (SNMP plus couteux).
         * Quotidien : scan Nmap du sous-reseau par defaut (decouverte auto).
         */
        $schedule->job(new DispatchMonitoringJobs(includeSnmp: false))
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->job(new DispatchMonitoringJobs(includeSnmp: true))
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // Scan Nmap quotidien : subnet .env, sinon auto-detection au moment du run.
        $schedule->call(function () {
            $resolved = app(NetworkDetectionService::class)->resolveDiscoverySubnet();
            NmapScanJob::dispatch($resolved['subnet']);
        })
            ->daily()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
