<?php

namespace App\Http\Controllers\Api\V1\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Http\Contains\HttpStatusCode;
use App\Mail\ProductInfoMail;
use App\Mail\SimpleMail;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Email",
 *     description="API Endpoints de Email"
 * )
 */

class EmailController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/emails",
     *     summary="Enviar correo electrónico",
     *     description="Envía un correo electrónico con asunto y mensaje a una dirección especificada",
     *     operationId="sendEmail",
     *     tags={"Email"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"to", "subject", "message"},
     *             @OA\Property(property="destinatario", type="string", format="email", example="destinatario@gmail.com", description="Correo del destinatario"),
     *             @OA\Property(property="asunto", type="string", example="Asunto del correo", description="Asunto del correo"),
     *             @OA\Property(property="mensaje", type="string", example="Este es el contenido del correo", description="Contenido del mensaje")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Correo enviado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Correo enviado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación de datos",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "to": {"The to field is required."},
     *                     "subject": {"The subject field is required."},
     *                     "message": {"The message field is required."}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Error al enviar el correo")
     *         )
     *     )
     * )
     */

    public function sendEmail(Request $request)
    {
        $request->validate([
            'destinatario' => 'required|email',
            'asunto' => 'required|string',
            'mensaje' => 'required|string',
        ]);

        try {
            Mail::to($request->destinatario)->send(new SimpleMail($request->only('asunto', 'mensaje')));
            return response()->json(['message' => 'Correo enviado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //Toma el template dinamico de productos
    public function sendEmailByProductLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'link' => 'required|string',
        ]);

        try {
            // Obtener el producto por su link
            $producto = \App\Models\Producto::with(['imagenes'])
                ->where('link', $request->link)
                ->firstOrFail();

            // Obtener la imagen de email (tipo='email') - Esta imagen contiene todo el diseño
            $imagenEmail = $producto->imagenes()->where('tipo', 'email')->first();

            // Preparar los datos para el email
            $productData = [
                'name' => $producto->nombre,
                'main_image' => $imagenEmail 
                    ? config('app.url') . $imagenEmail->url_imagen 
                    : asset('email/default-product.webp'),
                'video_url' => $producto->video_url ?? null,
                'client_name' => $request->name,
            ];

            Mail::to($request->email)->send(
                new ProductInfoMail(['product' => $productData], 'emails.product-generic')
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Correo enviado correctamente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error al enviar correo de producto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $request->email,
                'link' => $request->link ?? 'N/A',
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Hubo un problema al enviar el correo. Intente más tarde.'
            ], 500);
        }
    }

}
