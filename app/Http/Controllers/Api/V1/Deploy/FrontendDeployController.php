<?php

namespace App\Http\Controllers\Api\V1\Deploy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\TriggerFrontendDeployJob;


class FrontendDeployController extends Controller
{
    public function deploy(Request $request)
    {
        if($request->header('X-DEPLOY-KEY') !== config('app.deploy_key')){
            return response()->json(['message' => 'Unauthorized'], 401);
         }
         TriggerFrontendDeployJob::dispatch(
            'ManualTrigger',
             null
        );
            return response()->json([
                'message' => 'Deploy iniciado correctamente'
                ]);
        }
    }

