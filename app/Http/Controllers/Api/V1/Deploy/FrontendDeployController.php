<?php

namespace App\Http\Controllers\Api\V1\Deploy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\TriggerFrontendDeployJob;


class FrontendDeployController extends Controller
{
    public function deploy(Request $request)
{
    // El middleware 'auth:sanctum' y 'role:ADMIN' ya hicieron el trabajo pesado.
    // Aquí puedes añadir logs para saber QUIÉN disparó el deploy:
    \Log::info("Deploy iniciado por el usuario ID: " . $request->user()->id);

    // Opcional: Si aún quieres una capa extra interna, la clave se lee de config/app.php
    // pero ya NO viene del request del frontend.
    
    TriggerFrontendDeployJob::dispatch(
        'ManualTrigger',
        $request->user()->name // Pasamos el nombre del admin al Job si quieres
    );

    return response()->json([
        'message' => '🚀 Despliegue en cola. El frontend se actualizará pronto.'
    ]);
}
    }

