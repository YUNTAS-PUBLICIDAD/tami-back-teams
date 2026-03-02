<?php

namespace App\Http\Controllers\Api\V1\Reclamos;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\ContactMessage; // Asumiendo que tu modelo se llama así
use Exception;

use App\Traits\SafeErrorTrait;

class ContactMessageController extends Controller
{
    use SafeErrorTrait;

    /**
     * Guardar un nuevo mensaje de contacto.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validación directa en el controlador
            $validated = $request->validate([
                'name'    => 'required|string|max:150',
                'email'   => 'required|email|max:191',
                'subject' => 'nullable|string|max:200',
                'message' => 'required|string|max:2000',
            ]);

            // Creación del registro
            $contact = ContactMessage::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado correctamente.',
                'data'    => $contact
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => $this->safeErrorMessage($e, 'enviar el mensaje')
            ], 500);
        }
    }

    /**
     * Admin: Listar todos los mensajes recibidos.
     */
    public function index(Request $request): JsonResponse
    {
        $messages = ContactMessage::latest()
            ->paginate($request->get('perPage', 20));

        return response()->json([
            'success' => true, 
            'data'    => $messages
        ]);
    }

    /**
     * Admin: Ver un mensaje específico.
     */
    public function show($id): JsonResponse
    {
        try {
            $contact = ContactMessage::findOrFail($id);

            return response()->json([
                'success' => true, 
                'data'    => $contact
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Mensaje no encontrado.'
            ], 404);
        }
    }

    /**
     * Admin: Eliminar un mensaje.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $contact = ContactMessage::findOrFail($id);
            $contact->delete();

            return response()->json([
                'success' => true, 
                'message' => 'Mensaje eliminado'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => $this->safeErrorMessage($e, 'eliminar el mensaje')
            ], 500);
        }
    }
}