<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EnviarCampañaWhatsAppJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public string $telefono,
        public string $mensaje,
        public ?string $imagenPath,
        public string $nombre
    ) {}

    public function handle(): void
{
    try {

        $mensajeFinal = "Hola {$this->nombre}, {$this->mensaje}";

        $imageUrl = $this->imagenPath
            ? asset('storage/' . $this->imagenPath)
            : null;

        $url = config('services.whatsapp.base_url') . '/whatsapp/send-product-info';

        Http::post($url, [
            'phone' => $this->telefono,
            'message' => $mensajeFinal,
            'image' => $imageUrl,
        ]);

        Log::info('Campaña enviada', [
            'telefono' => $this->telefono
        ]);

    } catch (\Throwable $e) {

        Log::error('Error enviando campaña', [
            'telefono' => $this->telefono,
            'error' => $e->getMessage()
        ]);

        throw $e;
    }
}
}
