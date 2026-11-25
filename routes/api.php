<?php

use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/saludo', function () {
    return 'Hola saludos desde api.php';
});

// autenticación
Route::prefix('/v1/auth')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware(['auth:sanctum'])->group(function () {

        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

});

Route::middleware(['auth:sanctum'])->group(function () {

    // Reportes y acciones especiales: van antes del apiResource para que no
    // los capture la ruta "/recurso/{id}".
    Route::get('/producto/generar-excel', [ProductoController::class, 'exportarExcel'])->middleware('can:producto.ver');
    Route::get('/venta/{id}/generar-pdf', [VentaController::class, 'generarReciboPDF'])->middleware('can:venta.ver');
    Route::get('/compra/{id}/generar-pdf', [CompraController::class, 'generarReciboPDF'])->middleware('can:compra.ver');
    Route::get('/compra/reporte-pdf', [CompraController::class, 'exportarPDF'])->middleware('can:compra.ver');
    Route::get('/venta/reporte-pdf', [VentaController::class, 'exportarPDF'])->middleware('can:venta.ver');

    // subida de imagenes
    Route::post('/producto/{id}/subir-imagen', [ProductoController::class, 'actualizarImagen'])->middleware('can:producto.editar');

    // CRUD roles y permisos (Eloquent) + asignación de permisos a un rol
    Route::put('/rol/{rol}/permisos', [RoleController::class, 'actualizarPermisos'])->middleware('can:rol.editar');
    Route::post('/rol/{rol}/permisos/{permiso}', [RoleController::class, 'asignarPermiso'])->middleware('can:rol.editar');
    Route::delete('/rol/{rol}/permisos/{permiso}', [RoleController::class, 'quitarPermiso'])->middleware('can:rol.editar');

    // CRUD de recursos protegidos por permisos "recurso.accion"
    // (los permisos se crean en RolesYPermisosSeeder).
    $recursos = [
        '/categoria' => CategoriaController::class,
        '/usuario' => UserController::class,
        '/producto' => ProductoController::class,
        '/sucursal' => SucursalController::class,
        '/almacen' => AlmacenController::class,
        '/cliente' => ClienteController::class,
        '/proveedor' => ProveedorController::class,
        '/compra' => CompraController::class,
        '/venta' => VentaController::class,
        '/rol' => RoleController::class,
        '/permiso' => PermissionController::class,
    ];

    foreach ($recursos as $uri => $controlador) {
        $recurso = ltrim($uri, '/');

        Route::apiResource($uri, $controlador)->only(['index', 'show'])->middleware("can:$recurso.ver");
        Route::apiResource($uri, $controlador)->only(['store'])->middleware("can:$recurso.crear");
        Route::apiResource($uri, $controlador)->only(['update'])->middleware("can:$recurso.editar");
        Route::apiResource($uri, $controlador)->only(['destroy'])->middleware("can:$recurso.eliminar");
    }

});

Route::get('no-autorizado', function () {
    return ['mensaje' => 'No estas permitido para ver esta pagina'];
})->name('no-autorizado');
