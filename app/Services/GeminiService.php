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

            // 1. Creamos la estructura de contenidos del chat
            $contents = [];

            // 2. Inyectamos las instrucciones del sistema como el primerísimo mensaje (Rol de usuario para evitar quejas)
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => "INSTRUCCIONES DEL SISTEMA (Sigue estas reglas estrictamente):\n" . $systemInstruction]
                ]
            ];

            // Confirmamos la recepción de las instrucciones simulando una respuesta del modelo
            $contents[] = [
                'role' => 'model',
                'parts' => [
                    ['text' => "Entendido. Actuaré como Tami de Tami Maquinarias y solo usaré el inventario proporcionado."]
                ]
            ];

            // 3. Si ya hay historial acumulado en la caché, lo sumamos al flujo
            if (!empty($historialAnterior)) {
                $contents = array_merge($contents, $historialAnterior);
            }

            // 4. Añadimos el mensaje actual que acaba de enviar el usuario
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $mensaje]
                ]
            ];

            // 5. El payload definitivo solo lleva contents y la configuración básica
            $payload = [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 2048,
                ]
            ];

            // 6. Ejecutamos la petición HTTP a la URL v1 estable
            $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key=" . $apiKey;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            // 7. Procesamos la respuesta del asistente
            if ($response->successful()) {
                return $response->json('candidates.0.content.parts.0.text') ?? 'No pude procesar la respuesta.';
            }

            // Si vuelve a fallar, auditamos el JSON exacto en el log
            Log::error("Error de API Gemini v1: Code " . $response->status() . " - " . $response->body());
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