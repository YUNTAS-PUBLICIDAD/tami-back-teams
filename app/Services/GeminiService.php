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
        $this->apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');
        $this->model = config('services.gemini.model') ?? 'gemini-1.5-flash';
    }

    /**
     * Genera una respuesta manteniendo un flujo de conversación (Chat con memoria)
     */
    public function generarRespuestaConHistorial(string $mensaje, string $systemInstruction, array $historialAnterior): string
    {
        try {
            $apiKey = $this->apiKey;
            if (!$apiKey) {
                Log::error("Falta la variable GEMINI_API_KEY");
                return "Lo siento, estoy experimentando problemas técnicos para responder.";
            }

            // 1. Clonamos el historial previo de la caché (solo contiene roles user y model reales)
            $contents = !empty($historialAnterior) ? $historialAnterior : [];

            // 2. Inyectamos las reglas del negocio y el DTO de productos sutilmente dentro del mensaje actual.
            // De esta forma, en cada petición la IA recuerda quién es y qué vende sin romper el orden del chat.
            $mensajeInyectado = "CONTEXTO INVENTARIO Y REGLAS (Usa esto para responder de forma breve):\n" 
                              . $systemInstruction 
                              . "\n\nPREGUNTA ACTUAL DEL USUARIO:\n" 
                              . $mensaje;

            // 3. Añadimos este mensaje final al flujo que se enviará a Google
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $mensajeInyectado]
                ]
            ];

            // 4. Armamos el payload plano que la API v1 acepta sin chistar
            $payload = [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 2048,
                ]
            ];

            // 5. Petición HTTP a la URL v1 estable
            $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key=" . $apiKey;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            // 6. Procesamos el éxito
            if ($response->successful()) {
                return $response->json('candidates.0.content.parts.0.text') ?? 'No pude procesar la respuesta.';
            }

            // Si falla, dejamos el rastro exacto en el log de Laravel
            Log::error("Error de API Gemini v1 (Flujo Memoria): Code " . $response->status() . " - " . $response->body());
            return "Lo siento, estoy experimentando problemas técnicos para responder.";

        } catch (\Exception $e) {
            Log::error("Excepción en Gemini Chat: " . $e->getMessage());
            return "Lo siento, estoy experimentando problemas técnicos para responder.";
        }
    }

    /**
     * Genera una respuesta simple sin mantener historial previo (Stateless)
     */
    public function generarRespuesta(string $prompt, string $contexto): string
    {
        $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->apiKey}";

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
                        'generationConfig' => [
                            'temperature' => 0.3, // Menor temperatura = menos alucinación, más preciso
                        ]
                    ]);

            if ($response->successful()) {
                $resultado = $response->json();
                return $resultado['candidates'][0]['content']['parts'][0]['text'] ?? 'Ups, no pude procesar la respuesta.';
            }

            Log::error("Error de Gemini API Simple: " . $response->body());
            return "Lo siento, estoy experimentando problemas técnicos para responder.";

        } catch (\Throwable $e) {
            Log::error("Excepción al conectar con Gemini Simple: " . $e->getMessage());
            return "Error de conexión con el asistente virtual.";
        }
    }
}