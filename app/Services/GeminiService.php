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
        // Jalamos la nueva API Key de Groq desde el .env
        $this->apiKey = env('GROQ_API_KEY');
        // Por defecto usamos Llama 3 8B (Gratuito, rápido y eficiente)
        $this->model = env('GROQ_MODEL', 'llama3-8b-8192');
    }

    /**
     * Genera una respuesta manteniendo un flujo de conversación utilizando Groq (Llama 3)
     */
    public function generarRespuestaConHistorial(string $mensaje, string $systemInstruction, array $historialAnterior): string
    {
        try {
            $apiKey = $this->apiKey;
            if (!$apiKey) {
                Log::error("Falta la variable GROQ_API_KEY en el archivo .env");
                return "Lo siento, estoy experimentando problemas técnicos para responder.";
            }

            // 1. Inicializamos el array de mensajes con el rol 'system'
            // Aquí inyectamos directamente las reglas y el stock del ProductoDTO de forma aislada y limpia.
            $messages = [
                [
                    'role' => 'system',
                    'content' => "Eres Tami, el asistente virtual inteligente de Tami Maquinarias. Usa el siguiente inventario real y reglas de negocio para responder de manera breve y precisa:\n" . $systemInstruction
                ]
            ];

            // 2. Mapeamos e incorporamos el historial anterior si es que existe.
            // Nota técnica: Convertimos el rol 'model' de Gemini al estándar 'assistant' que usa Groq.
            if (!empty($historialAnterior)) {
                foreach ($historialAnterior as $chat) {
                    $role = ($chat['role'] === 'model' || $chat['role'] === 'assistant') ? 'assistant' : 'user';
                    
                    // Extraemos el texto adaptándolo si viene en formato estructurado de Gemini o plano
                    $text = '';
                    if (isset($chat['parts'][0]['text'])) {
                        $text = $chat['parts'][0]['text'];
                    } elseif (isset($chat['content'])) {
                        $text = $chat['content'];
                    }

                    if (!empty($text)) {
                        $messages[] = [
                            'role' => $role,
                            'content' => $text
                        ];
                    }
                }
            }

            // 3. Añadimos la pregunta actual del cliente al final del flujo
            $messages[] = [
                'role' => 'user',
                'content' => $mensaje
            ];

            // 4. Armamos el payload estándar compatible con la API de Groq
            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.4,
                'max_tokens' => 2048,
            ];

            // 5. Petición HTTP POST al endpoint oficial de Groq
            $url = "https://api.groq.com/openai/v1/chat/completions";

            $response = Http::withToken($apiKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            // 6. Procesamos la respuesta exitosa
            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?? 'No pude procesar la respuesta.';
            }

            // Si la API responde con un código de error, dejamos el rastro exacto en el log
            Log::error("Error de API Groq (Flujo Memoria): Code " . $response->status() . " - " . $response->body());
            return "Lo siento, estoy experimentando problemas técnicos para responder.";

        } catch (\Exception $e) {
            Log::error("Excepción en Groq Chat: " . $e->getMessage());
            return "Lo siento, estoy experimentando problemas técnicos para responder.";
        }
    }

    /**
     * Genera una respuesta simple sin mantener historial previo (Stateless)
     */
    public function generarRespuesta(string $prompt, string $contexto): string
    {
        $url = "https://api.groq.com/openai/v1/chat/completions";

        try {
            $response = Http::withToken($this->apiKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Contexto e Inventario Real de Tami Maquinarias:\n" . $contexto
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.3,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?? 'Ups, no pude procesar la respuesta.';
            }

            Log::error("Error de Groq API Simple: " . $response->body());
            return "Lo siento, estoy experimentando problemas técnicos para responder.";

        } catch (\Throwable $e) {
            Log::error("Excepción al conectar con Groq Simple: " . $e->getMessage());
            return "Error de conexión con el asistente virtual.";
        }
    }
}