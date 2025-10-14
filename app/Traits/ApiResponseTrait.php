<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Contains\HttpStatusCode;

trait ApiResponseTrait
{
    /**
     * Maneja excepciones de forma consistente con logging automático.
     *
     * @param \Exception $e Excepción capturada
     * @param string $operation Descripción de la operación que falló
     * @param bool $rollback Si se debe hacer rollback de la transacción activa
     * @return JsonResponse
     */
    protected function handleException(\Exception $e, string $operation, bool $rollback = false): JsonResponse
    {
        if ($rollback) {
            DB::rollBack();
        }

        Log::error("Error al {$operation}: {$e->getMessage()}", [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => "Error al {$operation}"
        ], HttpStatusCode::INTERNAL_SERVER_ERROR->value);
    }

    protected function notFound(string $resource = 'Recurso'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "{$resource} no encontrado"
        ], HttpStatusCode::NOT_FOUND->value);
    }

    protected function successResponse($data, string $message = 'Operación exitosa', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    protected function successMessage(string $message, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message
        ], $statusCode);
    }

    protected function validationError(string $message, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], HttpStatusCode::METHOD_UNPROCESSABLE_CONTENT->value);
    }

    protected function unauthorized(string $message = 'No autorizado'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], HttpStatusCode::UNAUTHORIZED->value);
    }

    protected function forbidden(string $message = 'Acceso denegado'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], HttpStatusCode::FORBIDDEN->value);
    }
}
