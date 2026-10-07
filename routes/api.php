<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get("/saludo", function(){
    return "Hola saludos desde api.php";
});

// autenticación
Route::prefix('/v1/auth')->group(function () {

    Route::post("/login", [AuthController::class, "login"]);
    Route::post("/register", [AuthController::class, "register"]);

    Route::middleware(['auth:sanctum'])->group(function(){

        Route::get("/profile", [AuthController::class, "profile"]);
        Route::post("/logout", [AuthController::class, "logout"]);
    });

});


Route::middleware(['auth:sanctum'])->group(function(){

    // subida de imagenes
    Route::post("/producto/{id}/subir-imagen", [ProductoController::class, "actualizarImagen"]);
 
    // php artisan make:controller CategoriaController --api
    // CRUD Categorias (apiResource: GET, POST, GET, PUT, DELETE)
    Route::apiResource("/categoria", CategoriaController::class);
    // php artisan make:controller UserController --api
    // CRUD usuarios (QueryBuilder)
    Route::apiResource("/usuario", UserController::class);
    Route::apiResource("/producto", ProductoController::class);
    // CRUD sucursales (Eloquent)
    Route::apiResource("/sucursal", SucursalController::class);


});


Route::get('no-autorizado', function(){
    return ["mensaje" => "No estas permitido para ver esta pagina"];
})->name('login');

