<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostAuth\PostAuth;
use App\Http\Contains\HttpStatusCode;
use App\Models\User;
use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Traits\SafeErrorTrait;
/**
 * @OA\Tag(
 * name="Autenticación",
 * description="Operaciones de autenticación de usuarios"
 * )
 */
class AuthController extends Controller
{
    use SafeErrorTrait;
    protected ApiResponseService $apiResponse;

    public function __construct(ApiResponseService $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Iniciar sesión y generar token de acceso",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password", "device_name"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@gmail.com"),
     *             @OA\Property(property="password", type="string", format="password", example="admin"),
    *              @OA\Property(property="device_name", type="string", example="navegador", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Autenticación exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Credenciales inválidas"
     *     )
     * )
     */
    public function login(PostAuth $request)
    {
        try {
            if (!Auth::attempt(['email' => $request['email'], 'password' => $request['password']])) {
                return $this->apiResponse->unauthorizedResponse('Las credenciales proporcionadas no son correctas.');
            }            
            
            $user = User::where('email', $request['email'])->firstOrFail();
            
            // Definir nombre del dispositivo
            $deviceName = $request['device_name'] ?? ($request->userAgent() ?? 'API Token');

            // Si se solicita sesión única, eliminar otros tokens
            if ($request->has('single_session') && $request['single_session']) {
                $user->tokens()->delete();
            }
            
            $token = $user->createToken($deviceName)->plainTextToken;

            return $this->apiResponse->successResponse([
                'token' => $token,
                'user' => $user,
            ], 'Inicio de sesión exitoso', HttpStatusCode::OK);
            
        } catch (\Exception $e) {
            return $this->apiResponse->errorResponse(
                $this->safeErrorMessage($e, 'iniciar sesión'),
                HttpStatusCode::INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Cerrar sesión y revocar token",
     *     tags={"Autenticación"},
     *     security={{"sanctum":{}}}, 
     *     @OA\Response(
     *         response=200,
     *         description="Sesión finalizada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cierre de sesión exitoso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No autorizado para cerrar sesión")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al cerrar sesión")
     *         )
     *     )
     * )
     */
    public function logout() {
        try {
            $user = Auth::user();
    
            if (!$user) {
                return $this->apiResponse->errorResponse(
                    'No tienes permiso para cerrar sesión.',
                    HttpStatusCode::UNAUTHORIZED
                );
            }

            $user->currentAccessToken()->delete();
    
            return $this->apiResponse->successResponse(
                [],
                'Cierre de sesión exitoso',
                HttpStatusCode::OK
            );
    
        } catch (\Exception $e) {
            return $this->apiResponse->errorResponse(
                $this->safeErrorMessage($e, 'cerrar sesión'),
                HttpStatusCode::INTERNAL_SERVER_ERROR
            );
        }
    }
}
