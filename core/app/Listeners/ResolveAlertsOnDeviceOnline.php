<?php

namespace App\Listeners;

use App\Events\DeviceBackOnline;
use App\Services\AlertEvaluator;

class ResolveAlertsOnDeviceOnline
{
    public function __construct(private readonly AlertEvaluator $evaluator)
    {
    }

    public function handle(DeviceBackOnline $event): void
    {
        $this->evaluator->evaluateDeviceOnline($event->device);
    }
}
