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
    
    $producto = Producto::with(['imagenWhatsapp', 'imagenes'])
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
        if ($request->email && !$cliente->email) {
            $updateData['email'] = $request->email;
        }
        if ($request->phone && !$cliente->celular) {
            $updateData['celular'] = $request->phone;
        }
        if (!empty($updateData)) {
            $cliente->update($updateData);
        }
    }

    try {
        $imagenParaEnviar = $producto->imagenWhatsapp ?? $producto->imagenes->where('tipo', 'galeria')->first();
        $defaultImageUrl = 'https://res.cloudinary.com/dshi5w2wt/image/upload/v1759791593/Copia_de_Imagen_de_Beneficios_2_1_u7a7tk.png';

        $imageUrl = $defaultImageUrl;
        if ($imagenParaEnviar) {
            $imageUrl = $imagenParaEnviar->url_imagen;
        }

        $whatsappServiceUrl = config('services.whatsapp.base_url');
        if (!$whatsappServiceUrl) {
            throw new \Exception('Configuración de WhatsApp no encontrada.');
        }

        $response = Http::post($whatsappServiceUrl . '/whatsapp/send-product-info', [
            'productName' => $producto->nombre,
            'description' => $this->formatHtmlForWhatsapp($producto->descripcion),
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

        $resultados['whatsapp'] = 'Mensaje de WhatsApp enviado correctamente ✅';
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

        $resultados['whatsapp'] = '❌ ' . $this->safeErrorMessage($e, 'enviar WhatsApp de producto', 500);
    }

    return response()->json([
        'message'   => 'Proceso finalizado con los siguientes resultados:',
        'resultados' => $resultados
    ], 200);
}

    public function sendPopUpDetails(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:100',
            'celular' => 'required|string|max:20',
            'email' => 'nullable|email|max:191',
        ]);

    $resultados = [];
    
    // Obtener la configuración del popup
    $setting = HomePopupSetting::first();
    if (!$setting) {
        return response()->json(['message' => 'No hay configuración de popup cargada.'], 400);
    }

    // Si el usuario envió sus datos desde el popup, asumimos que quiere la info. 
    // Solo validamos que exista un mensaje configurado.
    if (empty($setting->whatsapp_message)) {
        return response()->json(['message' => 'No hay un mensaje configurado para enviar.'], 400);
    }

    // Buscar o crear la fuente "Popup de Inicio"
    $source = ClienteSource::firstOrCreate(['name' => 'Popup de Inicio']);

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
        ]);
    } else if ($request->name && $cliente->name === 'Cliente Popup') {
        $cliente->update(['name' => $request->name]);
    } else {
        $updateData = [];
        if ($request->email && !$cliente->email) $updateData['email'] = $request->email;
        if ($request->celular && !$cliente->celular) $updateData['celular'] = $request->celular;
        if (!empty($updateData)) $cliente->update($updateData);
    }

    try {
        // Despachar el trabajo en segundo plano para no bloquear la respuesta al usuario
        // Usamos afterResponse para que se ejecute inmediatamente después de enviar la respuesta 200 al navegador
        \App\Jobs\ProcessPopUpSubmissionJob::dispatch(
            $cliente, 
            $setting, 
            $request->only(['name', 'celular', 'email'])
        )->afterResponse();

        return response()->json([
            'message'   => 'Proceso de popup iniciado correctamente',
            'resultados' => [
                'info' => 'Tu solicitud está siendo procesada. Recibirás la información en unos segundos ✅'
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

            $response = Http::post($whatsappServiceUrl . '/whatsapp/request-qr');

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

            $response = Http::post($whatsappServiceUrl . '/whatsapp/reset');

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