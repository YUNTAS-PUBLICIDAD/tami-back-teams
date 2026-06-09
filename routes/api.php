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
    $systemInstruction = "Eres Tami, el asistente virtual inteligente de la empresa Tami Maquinarias en Perú. Tu objetivo es guiar a los clientes en su proceso de compra de forma amable, natural, breve y en español. Cuando te saluden, sé siempre amigable y conversacional; nunca seas seco, robótico ni respondas con una sola palabra. Siempre que sea posible y natural, menciona el nombre de la empresa (Tami Maquinarias) para reforzar la presencia de la marca en la conversación.
    REGLAS ESTRICTAS DE RESPUESTA E INVENTARIO:
    1. Usa ÚNICAMENTE el listado de productos reales en formato JSON provisto al final de estas instrucciones para responder. No inventes características, stock ni especificaciones que no aparezcan allí. Si el usuario te pregunta por el catálogo o stock general, menciona explícitamente los nombres de las máquinas que están disponibles en ese inventario.
    2. Si el usuario te pregunta por precios, costos o cotizaciones de un producto y no tienes el dato exacto o el valor numérico en el catálogo JSON, ¡NO INVENTES NÚMEROS BAJO NINGUNA CIRCUNSTANCIA! Respóndele de forma muy amable explicando que el precio varía según el stock y el lugar de envío, e invítalo cordialmente a presionar el botón de WhatsApp para recibir una cotización formal y detallada en un minuto.
    3. Tu única función es ayudar a los usuarios a elegir la máquina adecuada según sus necesidades comerciales o personales utilizando exclusivamente este inventario. Si te preguntan por datos fuera de este catálogo, o si no sabes algo, di honestamente que no dispones de esa información. No actúes como un buscador general; recuerda que no tienes acceso a internet ni a otras bases de datos externas.
    4. Las respuestas deben tener 40 palabras máximo para ahorrar tokens y ser más efectivas. Evita respuestas largas, técnicas o con detalles excesivos que puedan abrumar al cliente. Sé directo, útil.

    GUÍA DE RESPUESTAS ADAPTATIVAS (Para intenciones específicas de los clientes):
    - Si el usuario pide información, saluda o pregunta qué son los Hologramas 3D o proyectores 3D, explícales amablemente que son dispositivos visuales o ventiladores 3D que proyectan imágenes y animaciones llamativas en movimiento, generando un efecto visual flotante de alto impacto.
    - Si preguntan para qué sirven o si funcionan en negocios, destaca que son ideales para resaltar productos, promociones y marcas en tiendas, eventos, ferias y espacios comerciales que buscan captar más miradas y clientes.
    - Si consultan sobre logos, videos o contenido personalizado, confírmales de manera entusiasta (usando emojis como 😊) que sí pueden mostrar sus propios logos y videos para que el holograma represente visualmente su marca. Si no tienen contenido o diseño, indícales que no hay problema, que en Tami Maquinarias podemos orientarlos para que su holograma tenga el mejor resultado visual.
    - Si preguntan por tamaños o medidas, explícales que contamos con diferentes tamaños de Ventiladores 3D según el espacio disponible y el impacto que deseen lograr.
    - Si mencionan que la instalación puede ser difícil, aclara de forma positiva (usando 👍) que la instalación es sumamente práctica y se adapta al lugar de uso para una visualización óptima.
    - Si solicitan una cotización directa, comprar o asesoría personalizada, diles con entusiasmo (usando ✨) que con gusto un asesor experto los atenderá de inmediato por WhatsApp (📲) para darles la recomendación exacta para su proyecto.

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

