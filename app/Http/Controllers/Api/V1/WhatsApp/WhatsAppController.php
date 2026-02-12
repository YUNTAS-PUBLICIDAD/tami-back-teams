<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppController extends Controller
{
    public function sendProductDetails(Request $request)
    {
        $resultados = [];
        
        $producto = Producto::with(['imagenWhatsapp', 'imagenes'])
                            ->where('link', $request->link)
                            ->first();
         if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }


        try {
            $imagenParaEnviar = $producto->imagenWhatsapp ?? $producto->imagenes->where('tipo', 'galeria')->first();
            $defaultImageUrl = 'https://res.cloudinary.com/dshi5w2wt/image/upload/v1759791593/Copia_de_Imagen_de_Beneficios_2_1_u7a7tk.png';


            $imageUrl = $defaultImageUrl;
            if ($imagenParaEnviar) {
                $imageUrl = $imagenParaEnviar->url_imagen;
            }

            $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL', 'http://localhost:3001/api');

            $response = Http::post($whatsappServiceUrl . '/whatsapp/send-product-info', [
                'productName' => $producto->nombre,
                'description' => $producto->descripcion,
                'phone'       => $request->phone,
                'email'       => $request->email,
                'imageData'   => $this->convertImageToBase64($imageUrl),
            ]);

            if (!$response->successful()) {
                Log::error('Error en la respuesta del servicio WhatsApp: ' . $response->body());
                throw new \Exception('Error en la respuesta del servicio WhatsApp: ' . $response->body());
            }

            $resultados['whatsapp'] = 'Mensaje de WhatsApp enviado correctamente ✅';
        } catch (\Throwable $e) {
            $resultados['whatsapp'] = '❌ Error al enviar WhatsApp: ' . $e->getMessage();
        }

        return response()->json([
            'message'   => 'Proceso finalizado con los siguientes resultados:',
            'resultados' => $resultados
        ], 200);
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
            $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL', 'http://localhost:3001/api');

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
            return response()->json([
                'message' => 'Error requesting QR code: ' . $e->getMessage()
            ], 500);
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

    // Obtener la plantilla para el modal
    public function show($name) {
        $template = WhatsappTemplate::where('name', $name)->first();
        return response()->json($template);
    }

    // Guardar cambios desde el modal
    public function update(Request $request, $name) {
        $template = WhatsappTemplate::updateOrCreate(
            ['name' => $name],
            ['content' => $request->content]
        );
        return response()->json(['message' => 'Plantilla actualizada', 'data' => $template]);
    }



}