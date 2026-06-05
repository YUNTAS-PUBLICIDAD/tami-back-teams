<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->model = config('services.gemini.model');
    }

    public function generarRespuesta(string $prompt, string $contexto): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        // Combinamos las instrucciones del sistema con la pregunta del usuario
        $promptCompleto = "Contexto e Inventario Real:\n{$contexto}\n\nPregunta del usuario: {$prompt}";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $promptCompleto]
                        ]
                    ]
                ],
                // Ajustamos la configuración para que sea una respuesta directa y comercial
                'generationConfig' => [
                    'temperature' => 0.3, // Menor temperatura = menos alucinación, más preciso
                    'maxOutputTokens' => 1200,
                ]
            ]);

            if ($response->successful()) {
                $resultado = $response->json();
                return $resultado['candidates'][0]['content']['parts'][0]['text'] ?? 'Ups, no pude procesar la respuesta.';
            }

            Log::error("Error de Gemini API: " . $response->body());
            return "Lo siento, estoy experimentando problemas técnicos para responder.";

        } catch (\Throwable $e) {
            Log::error("Excepción al conectar con Gemini: " . $e->getMessage());
            return "Error de conexión con el asistente virtual.";
        }
    }
}