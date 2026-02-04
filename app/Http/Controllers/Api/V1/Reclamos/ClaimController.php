<?php

namespace App\Http\Controllers\Api\V1\Reclamos;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Claim;
use Exception;

use App\Models\DocumentType;
use App\Models\ClaimType;
use App\Models\Producto;
use App\Models\ProductoEtiqueta;
use Illuminate\Support\Facades\Validator;


class ClaimController extends Controller
{
    /**
     * Store a newly created claim in storage.
     */
    public function store(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'first_name'       => 'required|string|max:150',
        'last_name'        => 'required|string|max:150',
        'document_type_id' => 'required|exists:document_types,id',
        'document_number'  => 'required|string|max:20',
        'email'            => 'required|email|max:191',
        'phone'            => 'nullable|string|max:20',
        'purchase_date'    => 'nullable|date',
        'producto_id'       => 'nullable|exists:productos,id',
        'claim_type_id'    => 'required|exists:claim_types,id',
        'detail'           => 'nullable|string',
        'claimed_amount'   => 'nullable|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors(),
        ], 422);
    }

    try {
        $data = $validator->validated();
        $data['claim_status_id'] = 1;

        $claim = Claim::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Reclamo registrado correctamente',
            'data'    => $claim
        ], 201);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error interno',
            'error'   => $e->getMessage()
        ], 500);
    }
}

    /**
     * Admin: Listar Reclamos
     */
    public function index(Request $request): JsonResponse
    {
    // 1. Iniciamos la consulta con las relaciones
    $query = Claim::with(['documentType', 'claimStatus', 'claimType']);

    // 2. Aplicamos el filtro si 'status_id' viene en la petición
    $query->when($request->filled('status_id'), function ($q) use ($request) {
        return $q->where('claim_status_id', $request->status_id);
    });

    // 3. Puedes agregar otros filtros fácilmente, por ejemplo, por número de documento
    $query->when($request->filled('document_number'), function ($q) use ($request) {
        return $q->where('document_number', 'like', '%' . $request->document_number . '%');
    });

    // 4. Ordenamos y paginamos
    $claims = $query->latest()->paginate($request->get('perPage', 20));

    return response()->json([
        'success' => true,
        'data'    => $claims
    ]);
}

    /**
     * Admin: Ver Detalle
     */
    public function show($id): JsonResponse
    {
        try {
            $claim = Claim::with(['documentType', 'claimStatus', 'claimType'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data'    => $claim
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reclamo no encontrado.'
            ], 404);
        }
    }

    /**
     * Admin: Actualizar Estado
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'status_id' => 'required|exists:claim_statuses,id' 
            ]);

            $claim = Claim::findOrFail($id);
            $claim->claim_status_id = $request->status_id;
            $claim->save();

            return response()->json([
                'success' => true, 
                'message' => 'Estado actualizado correctamente',
                'data'    => $claim
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function formData()
{
    try {
        return response()->json([
            'document_types' => DocumentType::select('id', 'label as name')->get(),
            'claim_types' => ClaimType::select('id', 'name')->get(),
            'products' => Producto::with(['imagenes' => function ($q) {
                $q->select('producto_id', 'url_imagen')
                  ->where('tipo', 'popup')
                  ->limit(1);
            
            }])
            
            ->select('id', 'nombre')
            ->get()
            ->map(function ($producto) {
                return [
                    'id' => $producto->id,
                    'name' => $producto->nombre,
                    'url_imagen' => $producto->imagenes->first()->url_imagen ?? null,
                ];
            }),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Error al cargar datos del formulario',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}