<?php

use App\Http\Controllers\Api\V2\V2ClienteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Blog\BlogController;
use App\Http\Controllers\Api\V1\User\UserController;
use App\Http\Controllers\Api\V1\Producto\ProductoController;
use App\Http\Controllers\Api\V1\Cliente\ClienteController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Api\V1\Email\EmailController;
use App\Http\Controllers\Api\V1\WhatsApp\WhatsAppController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Api\V1\WhatsApp\CampañaController;

use App\Http\Controllers\Api\V1\Reclamos\ClaimController;
use App\Http\Controllers\Api\V1\Reclamos\ContactMessageController;

use App\Http\Controllers\Api\V1\Deploy\FrontendDeployController;
use App\Http\Controllers\Api\V1\HomePopup\HomePopupSettingController;
use App\Http\Controllers\Api\V1\Chatbot\ChatbotController;

use App\Services\GeminiService;
use App\Models\Producto;
use App\DTO\ProductoDTO;
use Illuminate\Support\Facades\Cache;

Route::prefix('v1')->group(function () {

    Route::controller(HomePopupSettingController::class)->prefix('popup-settings')->group(function () {
        Route::middleware('throttle:api')->get('/public', 'showPublic');
    });

    Route::controller(AuthController::class)->prefix('auth')->group(function () {
        Route::middleware('throttle:login')->post('/login', 'login');
        Route::post('/logout', 'logout')->middleware(['auth:sanctum', 'role:ADMIN|USER']);
        Route::post('/refresh', 'refresh');
    });

    Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
        Route::apiResource('users', UserController::class);

        Route::controller(HomePopupSettingController::class)->prefix('admin/popup-settings')->group(function () {
            Route::get('/', 'showAdmin');
            Route::match(['POST', 'PUT', 'PATCH'], '/', 'update');
        });
    });

    // Crear cliente (Público)
    Route::post('clientes', [ClienteController::class, 'store']);
    // Clientes (Solo ADMIN y VENTAS)
    Route::middleware(['auth:sanctum', 'role:ADMIN|VENTAS'])->group(function () {
        Route::controller(ClienteController::class)->prefix('clientes')->group(function () {
            Route::get('/paginate', 'paginate');
            Route::get('/', 'getAdvancedList');
            Route::get('/{cliente}', 'show');
            Route::put('/{cliente}', 'update');
            Route::delete('/{cliente}', 'destroy');
        });
    });

    Route::controller(BlogController::class)->prefix('blogs')->group(function () {
        Route::middleware('throttle:api')->get('/', 'index');
        Route::middleware('throttle:api')->get('/paginate', 'paginate');
        Route::middleware('throttle:api')->get('/link/{link}', 'showLink');
        Route::middleware('throttle:api')->get('/{id}', 'show');

        #protegidas
        Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
            Route::post('/', 'store');
            Route::match(['POST', 'PUT'], '/{blog}', 'update');
            Route::delete('/{blog}', 'destroy');
        });
    });
    // Rutas de Email
    Route::controller(EmailController::class)->prefix('/emails')->group(function () {
        // Enviar correo general (Protegido por seguridad)
        Route::middleware(['throttle:public-forms'])->post('/', 'sendEmail');
        // Enviar correo de producto (Público para el popup de clientes)
        Route::middleware(['throttle:public-forms'])->post('/product-link', 'sendEmailByProductLink');
    });

    Route::controller(ProductoController::class)->prefix('productos')->group(function () {
        // Rutas públicas con Rate Limit (60/min)
        Route::middleware('throttle:api')->group(function () {
            Route::get('/', 'index');
            Route::get('/paginate', 'paginate');
            Route::get('/{id}', 'show');
            Route::get('/{id}/related', 'related');
            Route::get('/link/{link}', 'showByLink');
        });

        // Rutas protegidas (Solo ADMIN)
        Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
            Route::post('/', 'store');
            Route::match(['POST', 'PUT', 'PATCH'], '/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });
    });

    Route::controller(ChatbotController::class)->prefix('chatbot')->group(function () {
        Route::middleware('throttle:api')->group(function () {
            Route::get('/icon', 'getIcon');
            Route::get('/head-color', 'getHeaderColor');
            Route::get('/salute', 'getSaludo');
            Route::get('/posicion', [ChatbotController::class, 'getPosicion']);
        });

        // Rutas protegidas (Solo ADMIN)
        Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
            Route::post('/icon', 'updateIcon');
            Route::post('/head-color', 'updateHeaderColor');
            Route::post('/salute', 'updateSaludo');
            Route::post('/posicion', [ChatbotController::class, 'updatePosicion']);
        });
    });

    Route::controller(WhatsAppController::class)->prefix('whatsapp')->group(function () {
        Route::middleware('throttle:public-forms')->post('/solicitar-info-producto', 'sendProductDetails');
        Route::middleware('throttle:public-forms')->post('/popup-submission', 'sendPopUpDetails');

        // Rutas protegidas de WhatsApp Admin
        Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
            Route::post('/request-qr', 'requestQR');
            Route::post('/reset', 'resetSession');
            Route::get('/template/product/{productoId}', 'showByProduct');
            Route::match(['POST', 'PUT'], '/template/product/{productoId}', 'updateTemplateByProduct');
            Route::delete('/template/product/{productoId}', 'deleteTemplateByProduct');
        });
    });



    // ------------------- RECLAMOS (Público) -------------------
    Route::middleware('throttle:public-forms')->post('claims', [ClaimController::class, 'store']);

    // Datos para formularios públicos
    Route::middleware('throttle:api')->get('claim-form-data', [ClaimController::class, 'formData']);

    // ------------------- CONTACTO (Público) -------------------
    Route::middleware('throttle:public-forms')->post('contacto', [ContactMessageController::class, 'store']);

    // ------------------- ADMINISTRACIÓN RECLAMOS Y CONTACTO (Solo ADMIN) -------------------
    Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
        // Gestión de Reclamos
        Route::controller(ClaimController::class)->prefix('admin/claims')->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::patch('/{id}/status', 'updateStatus');
        });

        // Gestión de Mensajes de Contacto
        Route::controller(ContactMessageController::class)->prefix('admin/contacto')->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::delete('/{id}', 'destroy');
        });
    });

    // Deploy Frontend (solo ADMIN)
    Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
        Route::post(
            'frontend/deploy',
            [FrontendDeployController::class, 'deploy']
        );
    });




    // Las rutas individuales anteriores se movieron dentro del grupo protegido de WhatsAppController




    // Rutas para campañas de WhatsApp
    // ------------------- CAMPAÑAS WHATSAPP (Solo ADMIN) -------------------
    Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
        Route::controller(CampañaController::class)
            ->prefix('whatsapp/campañas')
            ->group(function () {
                // activar campaña
                Route::post('/activar', 'activar');

                // opcional: listar campañas
                Route::get('/', 'index');

                // opcional: ver campaña
                Route::get('/{id}', 'show');
            });
    });


    // ------------------- CHATBOT -------------------
    // Limitamos a 20 mensajes por minuto por usuario (IP) para evitar ataques de SPAM.
    Route::middleware('throttle:20,1')->post('chat/responder', [\App\Http\Controllers\Api\V1\Chatbot\ChatbotController::class, 'responder']);

});


// Route::prefix("v2")->group(function(){
//     Route::controller(V2ClienteController::class)->prefix("/clientes")->group(function(){
//         Route::middleware(["auth:sanctum", "role:ADMIN"])->group(function(){
//             Route::get("/", "index");
//             Route::get("/{id}", 'show');
//             Route::post("/", "store");
//             Route::put("/{id}", "update");
//             Route::delete("/{id}", "destroy");
//         });
//     });

// Route::controller(V2ProductoController::class)->prefix('productos')->group(function(){
//     Route::get('/', 'paginate');
//     Route::get('/all', 'index');
//     Route::get('/{id}', 'show');

//     Route::middleware(['auth:sanctum', 'role:ADMIN|USER', 'permission:ENVIAR'])->group(function () {});

//     Route::post('/', 'store');
//     Route::put('/{id}', 'update');
//     Route::delete('/{id}', 'destroy');
//     Route::get('/link/{link}', 'showByLink');
// });
// });


Route::controller(PermissionController::class)->prefix("permisos")->group(function () {
    Route::middleware(["auth:sanctum", 'role:ADMIN'])->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');

        //Asignaciones
        Route::post('/assign-permission', 'assignPermissionToRole');
        Route::post('/remove-permission', 'removePermissionFromRole');
        Route::get('/getRolePermissions/{id}', 'getRolePermissions');
    });
});

Route::controller(RoleController::class)->prefix("roles")->group(function () {
    Route::middleware(["auth:sanctum", 'role:ADMIN'])->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');

        //Asignaciones
        Route::post('/assign-role/{id}', 'assignRoleToUser');
        Route::post('/remove-role/{id}', 'removeRoleFromUser');
        Route::get('/getUserRoles/{id}', 'getUserRoles');
    });
});


//------------------NO TOCAR: IMPLEMENTACIÓN DE CHATBOT CON IA ---------------------------

Route::post('/v1/chatbot/sandbox-ia', function (Illuminate\Http\Request $request, GeminiService $geminiService) {
    $mensajeUsuario = $request->input('mensaje', 'Hola');
    $chatId = 'chat_' . $request->ip();

    // 1. Recuperamos el historial nativo de la caché
    $historial = Cache::get($chatId, []);

    // 2. Jalamos y limpiamos el inventario con tu DTO 🧼
    $productosBD = Producto::all();
    $productosLimpios = ProductoDTO::transformarColeccion($productosBD);

    // 3. El System Instruction solo lleva la identidad y el inventario (Estricto)
    $systemInstruction = "Eres Tami, la asesora virtual de Tami Maquinarias (Perú). Tu objetivo es guiar al cliente con un tono muy amable, entusiasta y vendedor. Mantén las respuestas fluidas, naturales y dinámicas (máximo 80 palabras por respuesta). Siempre que respondas, sé servicial y cierra con una pregunta abierta para mantener la conversación activa.

REGLAS ESTRICTAS:
1. Usa SOLO el catálogo JSON adjunto al final. Si un producto o dato no está ahí, di honestamente que no dispones de esa información en este momento. ¡No inventes stock ni características!
2. LISTAS DE PRODUCTOS: Si te preguntan qué vendes o qué hay disponible, lístalos usando viñetas de Markdown (- Producto), uno por línea. Agrega una breve frase introductoria y un cierre amigable para no sonar robótica.
3. PRECIOS: Si no hay valor numérico exacto en el JSON, ¡NO INVENTES NÚMEROS BAJO NINGUNA CIRCUNSTANCIA! Responde con empatía explicando que los precios varían según stock, importación o lugar de envío. Invítalos cordialmente a pulsar el botón de WhatsApp para darles una cotización formal y detallada.
4. DERIVACIÓN: Si piden cotizar, comprar o una asesoría personalizada, diles con entusiasmo (usando ✨ y 📲) que un asesor experto del equipo los atenderá de inmediato por WhatsApp para ayudarlos con su proyecto.

GUÍA DE HOLOGRAMAS / VENTILADORES 3D:
- Definición: Dispositivos visuales o ventiladores que proyectan imágenes y animaciones en movimiento, generando un efecto visual flotante 3D de alto impacto ideal para captar miradas y clientes en cualquier negocio.
- Contenido: Confirma de manera entusiasta (😊) que sí pueden mostrar sus propios logos y videos. Si no tienen contenido, aclara que en Tami Maquinarias los orientamos para que su holograma tenga el mejor resultado.
- Características: Son sumamente prácticos de instalar (👍) y contamos con variedad de tamaños según el espacio disponible.

INVENTARIO ACTUAL EN MYSQL (Usa solo esta información para validar stock y productos):" 
    . json_encode($productosLimpios);

    // 4. Le mandamos a Groq el mensaje, las reglas fijas y el historial acumulado
    $respuestaIA = $geminiService->generarRespuestaConHistorial($mensajeUsuario, $systemInstruction, $historial);

    // 5. Validamos el límite de mensajes (15 intercambios) para evitar abusos y sobrecarga de la IA

    if (count($historial) >= 30) {
        return response()->json([
            'usuario' => $mensajeUsuario,
            'asistente_ia' => "Has alcanzado el límite máximo de consultas para esta sesión de chat. Si necesitas más ayuda con Tami Maquinarias, por favor reinicia la ventana del chat o comunícate directamente con nuestro soporte.",
            'total_mensajes' => count($historial),
            'historial_nativo' => $historial
        ]);
    }

    // 6. Si la IA respondió con éxito, guardamos el nuevo par de mensajes en el formato plano de Groq
    if ($respuestaIA !== "Lo siento, estoy experimentando problemas técnicos para responder.") {
        $historial[] = ['role' => 'user', 'content' => $mensajeUsuario];
        $historial[] = ['role' => 'assistant', 'content' => $respuestaIA];

        // Guardamos en caché por 10 minutos
        Cache::put($chatId, $historial, now()->addMinutes(10));
    }

    return response()->json([
        'usuario' => $mensajeUsuario,
        'asistente_ia' => $respuestaIA,
        'total_mensajes' => count($historial),
        'historial_nativo' => $historial
    ]);
});

