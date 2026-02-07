<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TriggerFrontendDeployJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public string $eventName,
        public ?int $productoId = null
    ) {}

    public function handle(): void
    {
        Http::withToken(config('services.deploy.token'))
            ->post(config('services.deploy.webhook'), [
                'event' => $this->eventName,
                'producto_id' => $this->productoId,
            ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Deploy frontend falló', [
            'event' => $this->eventName,
            'producto_id' => $this->productoId,
            'error' => $e->getMessage(),
        ]);
    }
}

