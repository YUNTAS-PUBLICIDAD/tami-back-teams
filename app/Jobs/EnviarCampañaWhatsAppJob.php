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
        public string $celular,
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

        $url = config('services.whatsapp.base_url') . '/api/whatsapp/send-campaign';

        $response = Http::post($url, [
            'phone'   => $this->celular,
            'message' => $mensajeFinal,
            'image'   => $imageUrl,
        ]);

        // 👇 AGREGA ESTO para ver qué responde Node
        Log::info('Respuesta de WhatsApp Node', [
            'status'   => $response->status(),
            'body'     => $response->body(),
            'telefono' => $this->celular,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Error Node: ' . $response->body());
        }

    } catch (\Throwable $e) {
        Log::error('Error enviando campaña', [
            'telefono' => $this->celular,
            'error'    => $e->getMessage()
        ]);
        throw $e;
    }
}
}
