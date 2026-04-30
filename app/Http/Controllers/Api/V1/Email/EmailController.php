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
use App\Services\ApiResponseService;

/**
 * @OA\Tag(
 *     name="Email",
 *     description="API Endpoints de Email"
 * )
 */

use App\Traits\SafeErrorTrait;

class EmailController extends Controller
{
    use SafeErrorTrait;
    protected ApiResponseService $apiResponse;

    public function __construct(ApiResponseService $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

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
            // El envío de correo ahora está unificado en ClienteController@store.
            // Retornamos success para no romper el frontend que hace 2 llamadas.
            return response()->json([
                'status'  => 'success',
                'message' => 'Correo gestionado por ClienteController'
            ], 200);

        } catch (\Exception $e) {
            return $this->apiResponse->errorResponse(
                $this->safeErrorMessage($e, 'enviar el correo'),
                HttpStatusCode::INTERNAL_SERVER_ERROR
            );
        }
    }

}
