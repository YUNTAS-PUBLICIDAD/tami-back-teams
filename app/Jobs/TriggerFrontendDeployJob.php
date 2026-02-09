<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Services\GitHubAppService;

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
    $repo = config('services.github.repo');

    if (!$repo) {
        throw new \Exception('GitHub repo no configurado');
    }

    $githubApp = new GitHubAppService();
    $token = $githubApp->getInstallationToken();

    Http::withToken($token)
        ->acceptJson()
        ->post(
            "https://api.github.com/repos/{$repo}/dispatches",
            [
                'event_type' => 'rebuild-frontend',
                'client_payload' => [
                    'event' => $this->eventName,
                    'producto_id' => $this->productoId,
                ],
            ]
        );

    Log::info('Deploy frontend disparado', [
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
