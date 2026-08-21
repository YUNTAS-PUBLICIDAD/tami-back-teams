<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClienteSource;
use App\Models\Producto;
use App\Models\WhatsappMessageLog;
use App\Models\WhatsappTemplate;
use App\Models\HomePopupSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use App\Traits\SafeErrorTrait;
use App\Services\ApiResponseService;
use App\Http\Contains\HttpStatusCode;
use App\Mail\ClientRegistrationMail;
use Illuminate\Support\Facades\Mail;

class WhatsAppController extends Controller
{
    use \App\Traits\FormatsTextTrait;
    use SafeErrorTrait;
    protected ApiResponseService $apiResponse;

    public function __construct(ApiResponseService $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }
    // En WhatsAppController.php
public function sendProductDetails(Request $request)
{
    $request->validate([
        'link' => 'required|string|max:255',
        'phone' => 'required|string|max:20',
        'email' => 'nullable|email|max:191',
    ]);

    $resultados = [];

    $producto = Producto::with(['whatsappPasos', 'imagenes'])
                        ->where('link', $request->link)
                        ->first();
     if (!$producto) {
        return response()->json(['message' => 'Producto no encontrado'], 404);
    }

    // Buscar o crear cliente por email o celular
    $cliente = null;
    if ($request->email) {
        $cliente = Cliente::where('email', $request->email)->first();
    }
    if (!$cliente && $request->phone) {
        $cliente = Cliente::where('celular', $request->phone)->first();
    }

    $sourceProductoDetalle = ClienteSource::where('name', 'Producto detalle')->first();

    if (!$cliente) {
        // Crear nuevo cliente
        $cliente = Cliente::create([
            'name' => 'Cliente WhatsApp',
            'email' => $request->email,
            'celular' => $request->phone,
            'producto_id' => $producto->id,
            'source_id' => $sourceProductoDetalle?->id,
        ]);
    } else {
        // Actualizar cliente existente si hay datos nuevos
        $updateData = [];
        if ($request->email && $cliente->email !== $request->email) {
            $updateData['email'] = $request->email;
        }
        if ($request->phone && $cliente->celular !== $request->phone) {
            $updateData['celular'] = $request->phone;
        }
        if (!empty($updateData)) {
            $cliente->update($updateData);
        }
    }

    try {
        $whatsappPaso1 = $producto->whatsappPasos->firstWhere('paso', 1);
        $imagenParaEnviar = $whatsappPaso1 ?? $producto->imagenes->where('tipo', 'galeria')->first();
        $defaultImageUrl = 'https://res.cloudinary.com/dshi5w2wt/image/upload/v1759791593/Copia_de_Imagen_de_Beneficios_2_1_u7a7tk.png';

        $imageUrl = $defaultImageUrl;
        if ($imagenParaEnviar) {
            $imageUrl = $whatsappPaso1 ? $whatsappPaso1->imagen_url : $imagenParaEnviar->url_imagen;
        }

        $whatsappServiceUrl = config('services.whatsapp.base_url');
        if (!$whatsappServiceUrl) {
            throw new \Exception('Configuración de WhatsApp no encontrada.');
        }

        // Priorizar whatsapp_mensaje personalizado de la imagen tipo 'whatsapp'
        $mensajeWhatsapp = $whatsappPaso1?->mensaje;
        $descripcionFinal = !empty($mensajeWhatsapp) ? $mensajeWhatsapp : $producto->descripcion;

        $response = Http::timeout(10)->post($whatsappServiceUrl . '/whatsapp/send-product-info', [
            'productName' => $producto->nombre,
            'description' => $this->formatHtmlForWhatsapp($descripcionFinal),
            'phone'       => $request->phone,
            'email'       => $request->email,
            'imageData'   => $this->convertImageToBase64($imageUrl),
            'productoId'  => $producto->id,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Error en la respuesta del servicio WhatsApp: ' . $response->body());
        }

        // Registrar mensaje exitoso en BD
        WhatsappMessageLog::create([
            'producto_id' => $producto->id,
            'cliente_id' => $cliente->id,
            'phone' => $request->phone,
            'email' => $request->email,
            'status' => 'success',
            'image_url' => $imageUrl,
        ]);

        $resultados['whatsapp'] = 'Mensaje de WhatsApp enviado correctamente';
    } catch (\Throwable $e) {
        // Registrar mensaje fallido en BD
        if (isset($producto)) {
            WhatsappMessageLog::create([
                'producto_id' => $producto->id,
                'cliente_id' => $cliente?->id,
                'phone' => $request->phone,
                'email' => $request->email,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'image_url' => $imageUrl ?? null,
            ]);
        }

        $resultados['whatsapp'] =  $this->safeErrorMessage($e, 'enviar WhatsApp de producto', 500);
    }

    return response()->json([
        'message'   => 'Proceso finalizado con los siguientes resultados:',
        'resultados' => $resultados
    ], 200);
}

    public function sendPopUpDetails(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('sendPopUpDetails request:', $request->all());
        \Illuminate\Support\Facades\Log::info('Referer: ' . $request->header('referer'));

        $request->validate([
            'name' => 'nullable|string|max:100',
            'celular' => 'required|string|max:20',
            'email' => 'nullable|email|max:191',
            'link' => 'nullable|string|max:255',
            'producto_id' => 'nullable|exists:productos,id',
        ]);

    $resultados = [];

    // Obtener la configuración del popup
    $setting = HomePopupSetting::with(['whatsappSteps', 'emailSteps'])->first();
    if (!$setting) {
        return response()->json(['message' => 'No hay configuración de popup cargada.'], 400);
    }

    $globalWhatsappSteps = $setting->whatsappSteps->keyBy('sequence');
    $globalEmailSteps = $setting->emailSteps->keyBy('sequence');

    // =========================================================================
    // RESOLUCIÓN DE PRODUCTO — Cadena de fallbacks para identificar el producto
    // =========================================================================
    $referer = $request->header('referer');
    $eagerLoad = ['imagenes', 'whatsappTemplate', 'etiqueta', 'whatsappPasos', 'emailPasos'];
    $producto = null;
    $productoResolution = 'ninguno';

    // 1) Por producto_id explícito en el request
    if ($request->producto_id) {
        $producto = Producto::with($eagerLoad)->find($request->producto_id);
        if ($producto) $productoResolution = "producto_id={$request->producto_id}";
    }

    // 2) Por link del request (ignorar el dummy 'detalle')
    if (!$producto && $request->link && $request->link !== 'detalle') {
        $producto = Producto::with($eagerLoad)->where('link', $request->link)->first();
        if ($producto) $productoResolution = "link={$request->link}";
    }

    // 3) Por slug extraído del Referer (soporta tanto /producto/slug como detalle?link=slug)
    if (!$producto && $referer) {
        $parsedUrl = parse_url($referer);
        $slug = null;
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            if (isset($queryParams['link'])) {
                $slug = $queryParams['link'];
            }
        }
        if (!$slug && isset($parsedUrl['path'])) {
            if (preg_match('/\/productos?\/([^\/\?]+)/', $parsedUrl['path'], $matches)) {
                $slug = $matches[1];
            } elseif (preg_match('/\/catalogo-maquinarias\/([^\/\?]+)/', $parsedUrl['path'], $matches)) {
                $lastPart = $matches[1];
                if ($lastPart !== 'detalle') {
                    $slug = $lastPart;
                }
            }
        }
        if ($slug && $slug !== 'detalle') {
            $producto = Producto::with($eagerLoad)->where('link', $slug)->first();
            if ($producto) $productoResolution = "referer_slug={$slug}";
        }
    }

    // 4) Fallback: si el contexto indica popup de producto pero no se resolvió,
    //    buscar el cliente existente y usar su producto_id asociado.
    if (!$producto && ($request->link === 'detalle' || $request->input('source_id') == 2)) {
        $clienteFallback = null;
        if ($request->email) {
            $clienteFallback = Cliente::where('email', $request->email)->first();
        }
        if (!$clienteFallback && $request->celular) {
            $clienteFallback = Cliente::where('celular', $request->celular)->first();
        }
        if ($clienteFallback && $clienteFallback->producto_id) {
            $producto = Producto::with($eagerLoad)->find($clienteFallback->producto_id);
            if ($producto) $productoResolution = "cliente_producto_id={$clienteFallback->producto_id}";
        }
    }

    Log::info('sendPopUpDetails: resolución de producto', [
        'metodo' => $productoResolution,
        'producto_id' => $producto?->id,
        'producto_nombre' => $producto?->nombre,
    ]);

    if ($producto) {
        $emailPaso1 = $producto->emailPasos->firstWhere('paso', 1);
        $emailPaso2 = $producto->emailPasos->firstWhere('paso', 2);
        $emailPaso3 = $producto->emailPasos->firstWhere('paso', 3);
        $whatsappPaso1 = $producto->whatsappPasos->firstWhere('paso', 1);
        $whatsappPaso2 = $producto->whatsappPasos->firstWhere('paso', 2);
        $whatsappPaso3 = $producto->whatsappPasos->firstWhere('paso', 3);

        $payload = new \stdClass();
        $payload->email_enabled = $setting->email_enabled;

        // WhatsApp Pasos Lógica
        $hasProductWhatsappSteps = ($producto->whatsappPasos->count() > 0);

        if ($hasProductWhatsappSteps) {
            $payload->whatsapp_steps = [
                ['message' => $whatsappPaso1?->mensaje ?? null, 'image_url' => $whatsappPaso1?->imagen_url ?? null, 'delay_minutes' => $whatsappPaso1 ? $whatsappPaso1->delay_minutos : -1],
                ['message' => $whatsappPaso2?->mensaje ?? null, 'image_url' => $whatsappPaso2?->imagen_url ?? null, 'delay_minutes' => $whatsappPaso2 ? $whatsappPaso2->delay_minutos : -1],
                ['message' => $whatsappPaso3?->mensaje ?? null, 'image_url' => $whatsappPaso3?->imagen_url ?? null, 'delay_minutes' => $whatsappPaso3 ? $whatsappPaso3->delay_minutos : -1],
            ];
        } else {
            // Fallback global completo si el producto no tiene pasos configurados
            $payload->whatsapp_steps = [
                ['message' => $producto->whatsappTemplate ? $producto->whatsappTemplate->content : ($globalWhatsappSteps[1]->message ?? null), 'image_url' => $globalWhatsappSteps[1]->image_url ?? null, 'delay_minutes' => $globalWhatsappSteps[1]->delay_minutes ?? 0],
                ['message' => $globalWhatsappSteps[2]->message ?? null, 'image_url' => $globalWhatsappSteps[2]->image_url ?? null, 'delay_minutes' => $globalWhatsappSteps[2]->delay_minutes ?? 0],
                ['message' => $globalWhatsappSteps[3]->message ?? null, 'image_url' => $globalWhatsappSteps[3]->image_url ?? null, 'delay_minutes' => $globalWhatsappSteps[3]->delay_minutes ?? 0],
            ];
        }

        // Email Pasos Lógica
        $hasProductEmailSteps = ($producto->emailPasos->count() > 0);

        if ($hasProductEmailSteps) {
            $emailStepsData = [];

            foreach ([$emailPaso1, $emailPaso2, $emailPaso3] as $paso) {
                $emailMensaje = $paso?->mensaje ?? null;
                if ($request->name && is_string($emailMensaje)) {
                    $emailMensaje = str_replace('{{nombre}}', $request->name, $emailMensaje);
                }

                $emailStepsData[] = [
                    'subject' => $paso?->asunto ?? null,
                    'message' => $emailMensaje,
                    'image_url' => $paso?->imagen_url ?? null,
                    'btn_text' => $paso?->btn_text ?? '¡REGISTRARME!',
                    'btn_link' => $paso?->btn_link ?? url('/'),
                    'btn_bg_color' => $paso?->btn_bg_color ?? '#00AFA0',
                    'btn_text_color' => $paso?->btn_text_color ?? '#FFFFFF',
                    'delay_minutes' => $paso ? $paso->delay_minutos : -1,
                ];
            }

            $payload->email_steps = $emailStepsData;
        } else {
            // Fallback global completo si el producto no tiene pasos de Email configurados
            $emailStepsData = [];

            for ($i = 1; $i <= 3; $i++) {
                $emailMensaje = $globalEmailSteps[$i]->message ?? null;
                if ($request->name && is_string($emailMensaje)) {
                    $emailMensaje = str_replace('{{nombre}}', $request->name, $emailMensaje);
                }

                $emailStepsData[] = [
                    'subject' => $globalEmailSteps[$i]->subject ?? null,
                    'message' => $emailMensaje,
                    'image_url' => $globalEmailSteps[$i]->image_url ?? null,
                    'btn_text' => $globalEmailSteps[$i]->btn_text ?? null,
                    'btn_link' => $globalEmailSteps[$i]->btn_link ?? null,
                    'btn_bg_color' => $globalEmailSteps[$i]->btn_bg_color ?? null,
                    'btn_text_color' => $globalEmailSteps[$i]->btn_text_color ?? null,
                    'delay_minutes' => $globalEmailSteps[$i]->delay_minutes ?? 0,
                ];
            }

            $payload->email_steps = $emailStepsData;
        }
    } else {
        // Popup global (Inicio)
        $payload = new \stdClass();
        $payload->email_enabled = $setting->email_enabled;

        $payload->whatsapp_steps = $globalWhatsappSteps->values()->map(fn ($s) => [
            'message' => $s->message,
            'image_url' => $s->image_url,
            'delay_minutes' => $s->delay_minutes,
        ])->all();

        $payload->email_steps = $globalEmailSteps->values()->map(fn ($s) => [
            'subject' => $s->subject,
            'message' => $s->message,
            'image_url' => $s->image_url,
            'btn_text' => $s->btn_text,
            'btn_link' => $s->btn_link,
            'btn_bg_color' => $s->btn_bg_color,
            'btn_text_color' => $s->btn_text_color,
            'delay_minutes' => $s->delay_minutes,
        ])->all();
    }

    // Si el usuario envió sus datos desde el popup, asumimos que quiere la info.
    // Solo validamos que exista un mensaje configurado.
    if (empty($payload->whatsapp_steps[0]['message'] ?? null)) {
        return response()->json(['message' => 'No hay un mensaje configurado para enviar.'], 400);
    }

    // Buscar o crear la fuente
    $sourceName = $producto ? 'Producto detalle' : 'Popup de Inicio';
    $source = ClienteSource::firstOrCreate(['name' => $sourceName]);

    // Buscar o crear cliente
    $cliente = null;
    if ($request->email) {
        $cliente = Cliente::where('email', $request->email)->first();
    }
    if (!$cliente && $request->celular) {
        $cliente = Cliente::where('celular', $request->celular)->first();
    }

    if (!$cliente) {
        $cliente = Cliente::create([
            'name' => $request->name ?? 'Cliente Popup',
            'email' => $request->email,
            'celular' => $request->celular,
            'source_id' => $source->id,
            'producto_id' => $producto ? $producto->id : null,
        ]);
    } else {
        $updateData = [];

        // Actualizar el nombre si es distinto, así usamos el nombre más reciente proporcionado por el usuario
        if ($request->name && $cliente->name !== $request->name) {
            $updateData['name'] = $request->name;
        }

        if ($request->email && $cliente->email !== $request->email) $updateData['email'] = $request->email;
        if ($request->celular && $cliente->celular !== $request->celular) $updateData['celular'] = $request->celular;
        if ($producto && $cliente->producto_id !== $producto->id) $updateData['producto_id'] = $producto->id;
        if ($source && $cliente->source_id !== $source->id) $updateData['source_id'] = $source->id;

        if (!empty($updateData)) {
            $cliente->update($updateData);
            // Refrescar para asegurar que el job reciba el nombre actualizado
            $cliente->refresh();
        }
    }

    try {
        // Despachar el trabajo en segundo plano para no bloquear la respuesta al usuario
        // Usamos afterResponse para que se ejecute inmediatamente después de enviar la respuesta 200 al navegador
        \App\Jobs\ProcessPopUpSubmissionJob::dispatch(
            $cliente,
            $payload,
            $request->only(['name', 'celular', 'email'])
        )->afterResponse();

        return response()->json([
            'message'   => 'Proceso de popup iniciado correctamente',
            'resultados' => [
                'info' => 'Tu solicitud está siendo procesada. Recibirás la información en unos segundos'
            ]
        ], 200);
    } catch (\Throwable $e) {
        Log::error('Error al despachar job de popup: ' . $e->getMessage());
        return response()->json(['message' => 'Error al procesar la solicitud.'], 500);
    }
}

    public function convertImageToBase64($pathOrUrl)
    {
        if (filter_var($pathOrUrl, FILTER_VALIDATE_URL)) {
            $response = Http::get($pathOrUrl);

            if (!$response->successful()) {
                throw new \Exception('No se pudo descargar la imagen desde la URL');
            }

            $mimeType = $response->header('Content-Type');
            $base64 = base64_encode($response->body());

            return 'data:' . $mimeType . ';base64,' . $base64;
        }

        $storagePath = str_replace('/storage/', '', $pathOrUrl);

        if (!\Storage::disk('public')->exists($storagePath)) {
            throw new \Exception('La imagen no existe en el storage: ' . $storagePath);
        }

        $image = \Storage::disk('public')->get($storagePath);
        $base64 = base64_encode($image);

        $extension = pathinfo($pathOrUrl, PATHINFO_EXTENSION);
        $mimeType = match(strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg'
        };

        return 'data:' . $mimeType . ';base64,' . $base64;
    }

    public function requestQR()
    {
        try{
            $whatsappServiceUrl = config('services.whatsapp.base_url');
            if (!$whatsappServiceUrl) {
                return $this->apiResponse->errorResponse('Configuración de WhatsApp no encontrada.', HttpStatusCode::INTERNAL_SERVER_ERROR);
            }

            $response = Http::timeout(10)->post($whatsappServiceUrl . '/whatsapp/request-qr');

            if ($response->successful()) {
                return response()->json([
                    'message' => 'QR code requested successfully',
                    'data' => $response->json()
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Failed to request QR code',
                    'error' => $response->body()
                ], $response->status());
            }
        } catch (\Throwable $e) {
            return $this->apiResponse->errorResponse(
                $this->safeErrorMessage($e, 'solicitar código QR de WhatsApp'),
                HttpStatusCode::INTERNAL_SERVER_ERROR
            );
        }
    }
    public function resetSession()
    {
        try{
            $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL', 'http://localhost:3001/api');

            $response = Http::timeout(10)->post($whatsappServiceUrl . '/whatsapp/reset');

            if ($response->successful()) {
                return response()->json([
                    'message' => 'WhatsApp session reset successfully',
                    'data' => $response->json()
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Failed to reset WhatsApp session',
                    'error' => $response->body()
                ], $response->status());
            }
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error resetting WhatsApp session: ' . $e->getMessage()
            ], 500);
        }
    }

    // **NUEVA FUNCIÓN: Obtener plantilla por producto_id**
    public function showByProduct($productoId) {
        $template = WhatsappTemplate::where('producto_id', $productoId)->first();

        if (!$template) {
            return response()->json([
                'message' => 'No hay plantilla personalizada para este producto',
                'data' => null
            ], 200);
        }

        return response()->json([
            'message' => 'Plantilla encontrada',
            'data' => $template
        ], 200);
    }

    // **NUEVA FUNCIÓN: Actualizar/crear plantilla por producto_id**
    public function updateTemplateByProduct(Request $request, $productoId) {
        $request->validate([
            'content' => 'required|string'
        ]);

        // Verificar que el producto existe
        $producto = Producto::find($productoId);
        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        $template = WhatsappTemplate::updateOrCreate(
            ['producto_id' => $productoId],
            ['content' => $request->content]
        );

        return response()->json([
            'message' => 'Plantilla del producto actualizada',
            'data' => $template
        ], 200);
    }

    // **NUEVA FUNCIÓN: Eliminar plantilla personalizada de un producto**
    public function deleteTemplateByProduct($productoId) {
        $template = WhatsappTemplate::where('producto_id', $productoId)->first();

        if (!$template) {
            return response()->json(['message' => 'No hay plantilla para eliminar'], 404);
        }

        $template->delete();

        return response()->json([
            'message' => 'Plantilla eliminada correctamente'
        ], 200);
    }
}
