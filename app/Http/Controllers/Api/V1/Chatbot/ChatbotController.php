<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\ChatbotConfig;
use Illuminate\Support\Str;
use App\Services\ChatbotService;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psy\Readline\Hoa\Console;

class ChatbotController extends Controller
{
    use ApiResponseTrait; 

     public function ask(Request $request)
    {
        // Aceptamos 'chatbotTami' o 'mensaje' para evitar que Laravel rechace la petición de React
        $chatbotTami = $request->input('chatbotTami') ?? $request->input('mensaje');
        $platform = $request->input('platform') ?? $request->input('website') ?? 'website';

        if (empty($chatbotTami)) {
            return response()->json(['success' => false, 'response' => 'El mensaje es requerido.'], 400);
        }
        if (empty($platform)) {
            $platform = 'website';
        }

        $normalizedMessage = $this->normalizeText($chatbotTami);

        // 1. LÓGICA DE CONTADOR: Usamos el sessionId enviado por React
        $sessionId = $request->input('sessionId', $request->ip());
        $cacheKey = 'chat_count_' . $sessionId;
        $messageCount = Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $messageCount, 3600);

        // Obtener configuración dinámica
        $settings = Cache::remember('chatbot_config', 86400, function () {
            return ChatbotConfig::first();
        });

        $isBotActive =  true;

        if (!$isBotActive) {
            return response()->json(['success' => false, 'response' => 'El chat está temporalmente fuera de servicio.']);
        }

        $responseText = null; 

        // 3. N8N (Si no hay respuesta en FAQs)
        if (!$responseText) {
            try {
                $n8n_url = config('services.n8n.webhook_url');
                if (!$n8n_url) {
                    Log::error("La variable N8N_WEBHOOK_URL no está definida en el archivo .env.");
                } else {
                    $http = Http::timeout(8); 
                    if (app()->environment('local')) {
                        $http = $http->withoutVerifying();
                    }
                    
                    $response = $http->post($n8n_url, [
                        'chatbotTami' => $chatbotTami,
                        'sessionId' => $sessionId,
                        'platform' => $platform,
                    ]);
                    
                    if ($response->successful()) {
                    $data = $response->json();

                    $responseText =
                        $data['response']
                        ?? $data['output']
                        ?? $data['respuesta']
                        ?? null;

                    $showWhatsapp = $data['show_whatsapp'] ?? false;

                    if ($showWhatsapp) {
                        $enlaceWhatsapp = "https://wa.me/978883199";
                    }
                    } else {
                        Log::error('n8n respondió error', [
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error n8n: ' . $e->getMessage());
            }
        }
        
        // 4. Fallback (Si N8N falla o no hay respuesta)
        if (!$responseText) {
            $responseText = $settings?->fallback_message ?? 'Solicita una asesoría personalizada.';
            $enlaceWhatsapp = "https://wa.me/978883199";
        }

        return response()->json(array_filter([
            'success' => true,
            'response' => $responseText,
            'link_whatsapp' => $enlaceWhatsapp ?? null,
        ], fn($v) => $v !== null));
    }

    public function getIcon(ChatbotService $service): JsonResponse
    {
        $urlIcono = $service->getIcon();
    
        return response()->json([
            'success' => true,
            'url_icono' => $urlIcono,
        ]);
    }

    public function updateIcon(Request $request, ChatbotService $service): JsonResponse
    {
        $request->validate([
            'chatbot_icon' => 'required|image|max:2048', // max 2MB
        ]);

        $urlIcono = $service->updateIconChatbot($request->file('chatbot_icon'));

        return response()->json([
            'success' => true,
            'url_icono' => $urlIcono,
        ]);
    }
        public function getHeaderColor(ChatbotService $service): JsonResponse
        {
            $colors = $service->getHeaderColor();
        
            return response()->json([
                'success' => true,
                'color_inicial' => $colors['color_inicial'],
                'color_final' => $colors['color_final'],
            ]);
        }
    public function getSaludo(ChatbotService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'salute' => $service->getSaludo(),
        ]);
    }

    public function updateSaludo(Request $request, ChatbotService $service): JsonResponse
    {
        $request->validate([
            'salute' => 'required|string|max:1000',
        ]);

        $config = ChatbotConfig::firstOrCreate([]);
        $config->salute = $request->salute;
        $config->save();

        Cache::forget('chatbot_config');

        return response()->json([
            'success' => true,
            'salute' => $config->salute,
        ]);
    }

    public function updateHeaderColor(Request $request, ChatbotService $service): JsonResponse
    {
        $request->validate([
            'color_inicial' => 'required|string|max:20',
            'color_final' => 'required|string|max:20',
        ]);

        $colors = $service->updateHeaderColor(
            $request->color_inicial,
            $request->color_final
        );

        return response()->json([
            'success' => true,
            'data' => $colors,
        ]);
    }

    public function getPosicion(): JsonResponse
    {
        $config = ChatbotConfig::firstOrCreate([], [
            'is_left' => false,
        ]);

        return response()->json([
            'success' => true,
            'is_left' => (bool) $config->is_left,
        ]);
    }

    public function updatePosicion(Request $request): JsonResponse
    {
        $request->validate([
            'is_left' => 'required|boolean',
        ]);

        $config = ChatbotConfig::firstOrCreate([]);
        $config->is_left = $request->boolean('is_left');
        $config->save();

        Cache::forget('chatbot_config');

        return response()->json([
            'success' => true,
            'is_left' => (bool) $config->is_left,
        ]);
    }

    private function normalizeText($text)
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[.,¿?¡!]/', '', $text);
        $text = $this->removeAccents($text);
        return trim($text);
    }

    private function removeAccents($text)
    {
        $unwanted = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U'
        ];
        return strtr($text, $unwanted);
    }

    public function active()
    {
        $setting = ChatbotConfig::first();

        if (!$setting) {
            return response()->json([
                'active' => false,
                'chatbotTami' => 'No existe configuración del chatbot.'
            ]);
        }

        return response()->json([
            'active' => $setting->is_active,
            'data' => [
                'name' => $setting->nombre_bot,
                'avatar' => $setting->url_icono ?? null,
                'primaryColor' => $setting->color_primario,
                'triggerDelay' => 3000, 
                'mensajeBienvenida' => $setting->mensaje_bienvenida,
                'fallbackMessage' => $setting->fallback_message,
            ]
        ]);
    }

}
