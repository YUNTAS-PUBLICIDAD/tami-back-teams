<?php

namespace App\Http\Controllers\Api\V1\Deploy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\TriggerFrontendDeployJob;

class FrontendDeployController extends Controller
{
     public function deploy(Request $request)
    {
        
        TriggerFrontendDeployJob::dispatch(
            'ManualTrigger',          // origen del deploy
            auth()->id()              // usuario que lo ejecuta
        );

        return response()->json([
            'message' => 'Deploy iniciado correctamente'
        ]);
    }
}