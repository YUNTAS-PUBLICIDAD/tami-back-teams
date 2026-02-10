<?php

namespace App\Http\Controllers\Api\V1\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\StoreClienteRequest;
use App\Http\Requests\Cliente\UpdateClienteRequest;
use App\Models\Cliente;
use App\Services\ApiResponseService;
use Illuminate\Http\Response;
use App\Http\Contains\HttpStatusCode;
use App\Mail\ClientRegistrationMail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ClienteController extends Controller
{
    protected ApiResponseService $apiResponse;

    public function __construct(ApiResponseService $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/clientes",
     *     summary="Listar clientes",
     *     description="Obtiene la lista de todos los clientes registrados con sus datos básicos.",
     *     operationId="getClientes",
     *     tags={"Clientes"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de clientes obtenida exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Clientes obtenidos exitosamente"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Juan Pérez"
     *                     ),
     *                     @OA\Property(
     *                         property="email",
     *                         type="string",
     *                         format="email",
     *                         example="juan.perez@example.com"
     *                     ),
     *                     @OA\Property(
     *                         property="celular",
     *                         type="string",
     *                         example="+51 987654321"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Error al obtener los clientes: mensaje del error"
     *             )
     *         )
     *     )
     * )
     */

    public function index()
    {
        try{
            $cliente = Cliente::with(['producto', 'source'])->get();

            $showClient = $cliente->map(function ($cliente){
                return [
                    'id' => $cliente->id,
                    'name' => $cliente->name,
                    'email' => $cliente->email,
                    'celular' => $cliente->celular,
                    'producto' => $cliente->producto ? $cliente->producto->nombre : null,
                    'source' => $cliente->source ? $cliente->source->name : null,
                    'updated_at' => $cliente->updated_at,
                ];
            });

            return $this->apiResponse->successResponse(
                $showClient,
                'Clientes obtenidos exitosamente',
                HttpStatusCode::OK
            );
        }
        catch(\Exception $e){
            return response()->json(['error' => 'Error al obtener los clientes: ' . $e->getMessage()], HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        }

    }

    /**
     * @OA\Post(
     *     path="/api/v1/clientes",
     *     summary="Crear un nuevo cliente",
     *     description="Crea un nuevo cliente con los datos proporcionados.",
     *     operationId="createCliente",
     *     tags={"Clientes"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos del cliente a registrar",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name","email","celular"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Juan Pérez"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="juan.perez@example.com"
     *             ),
     *             @OA\Property(
     *                 property="celular",
     *                 type="string",
     *                 example="987654321"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cliente creado con éxito",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Cliente creado con éxito."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                 @OA\Property(property="email", type="string", example="juan.perez@example.com"),
     *                 @OA\Property(property="celular", type="string", example="987654321")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Error al crear el cliente: mensaje del error")
     *         )
     *     )
     * )
     */

    public function store(StoreClienteRequest $request)
    {
        $datosValidados = $request->validated();
        DB::beginTransaction();

        try
        {
            $cliente = Cliente::create(
            [
                'name' => $datosValidados['name'],
                'email' => $datosValidados['email'],
                'celular' => $datosValidados['celular']
            ]
            );

            Mail::to($request->email)->send(new ClientRegistrationMail($request->only('name', 'email', 'celular')));

            DB::commit();
            return $this->apiResponse->successResponse($cliente->fresh(), 'Cliente creado con éxito.', HttpStatusCode::CREATED);
        }
        catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear el cliente: ' . $e->getMessage()], HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/clientes/{id}",
     *     summary="Obtener un cliente",
     *     description="Obtiene la información de un cliente específico por su ID.",
     *     operationId="getClienteById",
     *     tags={"Clientes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del cliente a obtener",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cliente obtenido exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Cliente obtenido exitosamente"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="Juan Pérez"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     example="juan.perez@example.com"
     *                 ),
     *                 @OA\Property(
     *                     property="celular",
     *                     type="string",
     *                     example="+51 987654321"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Cliente no encontrado"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Error al obtener el cliente: mensaje del error"
     *             )
     *         )
     *     )
     * )
     */

    public function show(string $id)
    {
        try {
            $cliente = Cliente::find($id);

            if (!$cliente) {
                return response()->json(['error' => 'Cliente no encontrado'], HttpStatusCode::NOT_FOUND->value);
            }

            $showClient = [
                'id' => $cliente->id,
                'name' => $cliente->name,
                'email' => $cliente->email,
                'celular' => $cliente->celular,
            ];

            return $this->apiResponse->successResponse(
                $showClient,
                'Cliente obtenido exitosamente',
                HttpStatusCode::OK
            );
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Error al obtener el cliente: ' . $e->getMessage()],
                HttpStatusCode::INTERNAL_SERVER_ERROR->value
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/clientes/{id}",
     *     summary="Actualizar un cliente",
     *     description="Actualiza la información de un cliente existente por su ID.",
     *     operationId="updateCliente",
     *     tags={"Clientes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del cliente a actualizar",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos para actualizar el cliente",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name","email","celular"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Juan Pérez"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="juan.perez@example.com"
     *             ),
     *             @OA\Property(
     *                 property="celular",
     *                 type="string",
     *                 example="987654321"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cliente actualizado con éxito",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Cliente actualizado con éxito."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                 @OA\Property(property="email", type="string", example="juan.perez@example.com"),
     *                 @OA\Property(property="celular", type="string", example="987654321")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Cliente no encontrado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Error al actualizar el cliente: mensaje del error")
     *         )
     *     )
     * )
     */

    public function update(UpdateClienteRequest $request, string $id)
    {
        $datosValidados = $request->validated();
        DB::beginTransaction();

        try
        {
            $cliente = Cliente::find($id);
            if ($cliente == false) {
                return response()->json(['error' => 'Cliente no encontrado'], HttpStatusCode::NOT_FOUND->value);
            }

            $camposActualizar = [];
            
            foreach (['name', 'email', 'celular'] as $campo) {
                if (array_key_exists($campo, $datosValidados)) {
                    $camposActualizar[$campo] = $datosValidados[$campo];
                }
            }

            if (!empty($camposActualizar)) {
                $cliente->update($camposActualizar);
            }

            /*$cliente->update([
                'name' => $datosValidados['name'],
                'email' => $datosValidados['email'],
                'celular' => $datosValidados['celular']
            ]);*/

            DB::commit();
            return $this->apiResponse->successResponse($cliente->fresh(), 'Cliente actualizado con éxito.', HttpStatusCode::OK);
        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al actualizar el cliente: ' . $e->getMessage()], HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/clientes/{id}",
     *     summary="Eliminar un cliente",
     *     description="Elimina un cliente existente por su ID.",
     *     operationId="deleteCliente",
     *     tags={"Clientes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del cliente a eliminar",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cliente eliminado exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Cliente eliminado exitosamente"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Cliente no encontrado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Error al eliminar el cliente: mensaje del error")
     *         )
     *     )
     * )
     */

    public function destroy(string $id)
    {
        DB::beginTransaction();

        try{
            $cliente = Cliente::find($id);
            if ($cliente == false) {
                return response()->json(['error' => 'Cliente no encontrado'], HttpStatusCode::NOT_FOUND->value);
            }

            $cliente->delete();

            DB::commit();

            return $this->apiResponse->successResponse(
                null,
                'Cliente eliminado exitosamente',
                HttpStatusCode::OK
            );
        }
        catch(\Exception $e){
            return response()->json(['error' => 'Error al eliminar el cliente: ' . $e->getMessage()], HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        }
    }

    public function paginate(Request $request)
    {
        $perPage = $request->get('perPage', 10);
        $page = $request->get('page', 1);

        $clientes = Cliente::paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'=> $clientes->items()
        ]);
    }
}
