<?php

namespace App\Http\Controllers\Api\V1\Deploy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\TriggerFrontendDeployJob;


class FrontendDeployController extends Controller
{
     public function deploy(Request $request)
    {
        
        // Dispatch the job to trigger the frontend deployment
        TriggerFrontendDeployJob::dispatch();

        return response()->json(['message' => 'Frontend deployment triggered successfully.']);


    }

}