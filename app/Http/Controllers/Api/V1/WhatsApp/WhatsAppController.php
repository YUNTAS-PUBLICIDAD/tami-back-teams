<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    public function sendProductDetails(Request $request)
    {
        $resultados = [];
        $producto = Producto::where('link', $request->link)->first();
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
                    $imageUrl = env('APP_URL') . $imagenParaEnviar->url_imagen;
                }

                Log::info('Enviando imagen a WhatsApp desde la URL: ' . $imageUrl);
            
            $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL', 'http://localhost:5111/api');

            Http::post($whatsappServiceUrl . '/send-product-info', [
                'productName' => $producto->nombre,
                'description' => $producto->descripcion,
                'phone'       => "+51" . $request->phone,
                'email'       => $request->email,
                'imageData'   => $this->convertImageToBase64($imageUrl),
            ]);
            $resultados['whatsapp'] = 'Mensaje de WhatsApp enviado correctamente ✅';
        } catch (\Throwable $e) {
            $resultados['whatsapp'] = '❌ Error al enviar WhatsApp: ' . $e->getMessage();
        }

        return response()->json([
            'message'   => 'Proceso finalizado con los siguientes resultados:',
            'resultados' => $resultados
        ], 200);
    }

    public function convertImageToBase64($url)
    {
        $response = Http::get($url);

        if (!$response->successful()) {
            throw new \Exception('No se pudo descargar la imagen');
        }

        $mimeType = $response->header('Content-Type');

        $base64 = base64_encode($response->body());

        $imageData = 'data:' . $mimeType . ';base64,' . $base64;

        return $imageData;
    }
}
