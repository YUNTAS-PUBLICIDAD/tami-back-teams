<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\ActivarCampañaRequest;
use App\Models\Campaña;
use App\Models\Cliente;
use App\Jobs\EnviarCampañaWhatsAppJob;
use Illuminate\Support\Facades\DB;

class CampañaController extends Controller
{
    public function activar(ActivarCampañaRequest $request)
    {
        // Verificar que existan clientes para el producto
        $clientes = Cliente::where('producto_id', $request->producto_id)->get();
        
        if ($clientes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay clientes registrados para este producto',
                'error_code' => 'NO_CLIENTS_FOUND'
            ], 404);
        }

        try {
            return DB::transaction(function () use ($request, $clientes) {
                // Guardar imagen
                $imagenPath = null;
                if ($request->hasFile('imagen')) {
                    $imagenPath = $request->file('imagen')->store('campanas', 'public');
                    
                    if (!$imagenPath) {
                        throw new \Exception('Error al guardar la imagen');
                    }
                }

                // Crear la campaña
                $campaña = Campaña::create([
                    'nombre' => $request->nombre,
                    'producto_id' => $request->producto_id,
                    'contenido_personalizado' => $request->contenido_personalizado,
                    'imagen_path' => $imagenPath,
                ]);

                if (!$campaña) {
                    throw new \Exception('Error al crear la campaña');
                }

                // Programar envío de mensajes
                $delay = 0;
                $jobsDispatchedCount = 0;

                foreach ($clientes as $cliente) {
                    EnviarCampañaWhatsAppJob::dispatch(
                        $cliente->celular,
                        $campaña->contenido_personalizado,
                        $campaña->imagen_path,
                        $cliente->name,
                        $campaña->id,
                        $cliente->id
                    )->delay(now()->addSeconds($delay));
                    
                    $delay += 10;
                    $jobsDispatchedCount++;
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Campaña activada y mensajes programados',
                    'data' => [
                        'campaña_id' => $campaña->id,
                        'total_clientes' => $clientes->count(),
                        'mensajes_programados' => $jobsDispatchedCount
                    ]
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la campaña',
                'error_code' => 'CAMPAIGN_PROCESSING_ERROR',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
