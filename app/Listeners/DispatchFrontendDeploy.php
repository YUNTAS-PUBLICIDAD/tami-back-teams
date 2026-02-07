<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\TriggerFrontendDeployJob;
class DispatchFrontendDeploy
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        TriggerFrontendDeployJob::dispatch(
            class_basename($event),
            $event->producto->id ?? null
        );
    }
}

