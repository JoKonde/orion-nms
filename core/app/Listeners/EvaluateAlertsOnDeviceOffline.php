<?php

namespace App\Listeners;

use App\Events\DeviceWentOffline;
use App\Services\AlertEvaluator;

class EvaluateAlertsOnDeviceOffline
{
    public function __construct(private readonly AlertEvaluator $evaluator)
    {
    }

    public function handle(DeviceWentOffline $event): void
    {
        $this->evaluator->evaluateDeviceOffline($event->device);
    }
}
