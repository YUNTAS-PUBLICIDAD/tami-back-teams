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


Route::prefix('v1')->group(function () {

    Route::controller(AuthController::class)->prefix('auth')->group(function () {
        Route::post('/login', 'login');
        Route::post('/logout', 'logout')->middleware(['auth:sanctum', 'role:ADMIN|USER']);
    });

    Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
        Route::apiResource('users', UserController::class);
    });

    Route::controller(ClienteController::class)->prefix('clientes')->group(function () {
        Route::get('/paginate', 'paginate');
    });

    Route::apiResource('clientes', ClienteController::class);

    Route::controller(BlogController::class)->prefix('blogs')->group(function () {
        Route::get('/', 'index');
        Route::get('/paginate', 'paginate');
        Route::get('/link/{link}', 'showLink');
        Route::get('/{id}', 'show');

        Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {});
        Route::post('/', 'store');
        Route::post('/{blog}', 'update');
        //Route::patch('/{blog}', 'update');
        Route::delete('/{blog}', 'destroy');
    });

    Route::controller(EmailController::class)->prefix('/emails')->group(function () {
        Route::post('/', 'sendEmail');
        Route::post('/product-link', 'sendEmailByProductLink');
    });

    Route::controller(ProductoController::class)->prefix('productos')->group(function(){
        Route::get('/', 'index');
        Route::get('/paginate', 'paginate');
        Route::get('/{id}', 'show');

        Route::get('/{id}/related', 'related');

        Route::middleware(['auth:sanctum', 'role:ADMIN|USER', 'permission:ENVIAR'])->group(function () {});

        Route::post('/', 'store');
        Route::put('/{id}', 'update');
        Route::patch('/{id}', 'update'); // Añadida ruta PATCH
        Route::delete('/{id}', 'destroy');
        Route::get('/link/{link}', 'showByLink');
    });

    Route::prefix('whatsapp')->group(function () {
        Route::post('/solicitar-info-producto', [WhatsAppController::class, 'sendProductDetails']);
        Route::get('/request-qr', [WhatsAppController::class, 'requestQR']);
        Route::post('/reset', [WhatsAppController::class, 'resetSession']);
    });
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
    Route::middleware(["auth:sanctum"])->group(function () {
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
    Route::middleware(["auth:sanctum"])->group(function () {
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
