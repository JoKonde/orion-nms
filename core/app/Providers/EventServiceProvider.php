<?php

namespace App\Providers;

use App\Events\AgentWentOffline;
use App\Events\AlertRaised;
use App\Events\DeviceBackOnline;
use App\Events\DeviceDiscovered;
use App\Events\DeviceWentOffline;
use App\Events\MetricReceived;
use App\Listeners\EvaluateAlertsOnDeviceOffline;
use App\Listeners\EvaluateAlertsOnMetricReceived;
use App\Listeners\LogAgentOffline;
use App\Listeners\LogAlertRaised;
use App\Listeners\LogDeviceDiscovered;
use App\Listeners\LogMetricReceived;
use App\Listeners\ResolveAlertsOnDeviceOnline;
use App\Listeners\UpdateDeviceOnAgentOffline;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Module 03 : quand un agent passe offline, plusieurs listeners reagissent.
        AgentWentOffline::class => [
            UpdateDeviceOnAgentOffline::class,
            LogAgentOffline::class,
        ],

        MetricReceived::class => [
            LogMetricReceived::class,
            EvaluateAlertsOnMetricReceived::class,
        ],

        DeviceDiscovered::class => [
            LogDeviceDiscovered::class,
        ],

        AlertRaised::class => [
            LogAlertRaised::class,
        ],

        DeviceWentOffline::class => [
            EvaluateAlertsOnDeviceOffline::class,
        ],

        DeviceBackOnline::class => [
            ResolveAlertsOnDeviceOnline::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
