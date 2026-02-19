<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Campaña;
use App\Models\Cliente;
use App\Jobs\EnviarCampañaWhatsAppJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;



class CampañaController extends Controller
{
    public function activar(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'producto_id' => 'required|exists:productos,id',
            'contenido_personalizado' => 'required',
            'imagen' => 'nullable|image|max:2048', // max 2MB
        ]);

        //guardar imagen
        $imagenPath = null;
        if ($request->hasFile('imagen')) {
            $imagenPath = $request->file('imagen')->store('campañas', 'public');
        }
        // Crear la campaña
        $campaña = Campaña::create([
            'nombre' => $request->nombre,
            'producto_id' => $request->producto_id,
            'contenido_personalizado' => $request->contenido_personalizado,
            'imagen_path' => $imagenPath,
        ]);
        // Obtener clientes
        $clientes = Cliente::where('producto_id', $request->producto_id)->get();
        $delay = 0;
        foreach ($clientes as $cliente) {
            EnviarCampañaWhatsAppJob::dispatch(
                $cliente->telefono,
                $campaña->contenido_personalizado,
                $campaña->imagen_path,
                $cliente->name
            )->delay(now()->addSeconds($delay));
            $delay += 10; // Incrementar el delay en 10 segundos para cada cliente
        }
        return response()->json([
            'message' => 'Campaña activada y mensajes programados',
            'total_clientes' => $clientes->count()
            ], 200);
    }
}
