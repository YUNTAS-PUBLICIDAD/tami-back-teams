<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\ChatbotConfig;
use Illuminate\Support\Str;
use App\Services\ChatbotService;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    use ApiResponseTrait; 

     public function ask(Request $request)
    {
        // Aceptamos 'chatbotTami' o 'mensaje' para evitar que Laravel rechace la petición de React
        $chatbotTami = $request->input('chatbotTami') ?? $request->input('mensaje');

        if (empty($chatbotTami)) {
            return response()->json(['success' => false, 'response' => 'El mensaje es requerido.'], 400);
        }

        $normalizedMessage = $this->normalizeText($chatbotTami);

        // 1. LÓGICA DE CONTADOR: Usamos el sessionId enviado por React
        $sessionId = $request->input('sessionId', $request->ip());
        $cacheKey = 'chat_count_' . $sessionId;
        $messageCount = Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $messageCount, 3600);

        // Obtener configuración dinámica
        $settings = Cache::remember('chatbot_settings', 86400, function () {
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
                    $http = Http::timeout(30);
                    if (app()->environment('local')) {
                        $http = $http->withoutVerifying();
                    }
                    
                    $response = $http->post($n8n_url, [
                        'chatbotTami' => $chatbotTami,
                        'sessionId' => $sessionId
                    ]);
                    
                    if ($response->successful()) {
                    $data = $response->json();

                    $responseText =
                        $data['response']
                        ?? $data['output']
                        ?? $data['respuesta']
                        ?? null;
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
            $responseText = $settings?->fallback_message ?? "No estoy seguro de eso, pero puedes contactarnos al 936910425.";
        }

        // 5. INYECCIÓN DEL BOTÓN EN EL TERCER MENSAJE EXACTO
        if ($messageCount === 3) {
            // Leer número y mensaje desde la configuración de Laravel
            $waPhone = config('services.whatsapp.phone') ?? '936910425';
            $waMessage = config('services.whatsapp.message') ?? 'Hola, quiero una cotización.';

            // URL dinámica generada desde el .env
            $whatsappUrl = "https://wa.me/{$waPhone}?text=" . urlencode($waMessage);
            
            // Botón HTML premium inyectado
            $botonWhatsApp = "
                <div style='margin-top: 15px; text-align: center; width: 100%;'>
                    <a href='{$whatsappUrl}' target='_blank' style='display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #25D366, #128C7E); color: #ffffff; padding: 12px 20px; border-radius: 50px; text-decoration: none; font-weight: bold; font-family: system-ui, -apple-system, sans-serif; font-size: 14px; box-shadow: 0 4px 12px rgba(37,211,102,0.4); width: 100%; box-sizing: border-box; transition: transform 0.2s;'>
                        <svg xmlns='http://www.svg.org/2000/svg' width='20' height='20' fill='currentColor' viewBox='0 0 16 16' style='margin-right: 8px;'>
                            <path d='M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z'/>
                        </svg>
                        Cotizar por WhatsApp
                    </a>
                </div>";
            
            $responseText .= $botonWhatsApp;
        }

        return response()->json([
            'success' => true,
            'response' => $responseText
        ]);
    }

       public function getIcon(): JsonResponse
        {
            return response()->json([
                'success' => true,
                'url_icono' => null,
                'icon' => null,
            ]);
        }

        public function getHeaderColor(): JsonResponse
        {
            return response()->json([
                'success' => true,
                'color_inicial' => '#2A938B',
                'color_final' => '#0D2D2B',
                'color' => '#2A938B',
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
