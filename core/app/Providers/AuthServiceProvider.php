<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Device::class => \App\Policies\DevicePolicy::class,
        \App\Models\Agent::class => \App\Policies\AgentPolicy::class,
        \App\Models\AlertRule::class => \App\Policies\AlertRulePolicy::class,
        \App\Models\Alert::class => \App\Policies\AlertPolicy::class,
        \App\Models\Incident::class => \App\Policies\IncidentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
