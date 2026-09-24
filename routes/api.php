<?php

use App\Http\Controllers\AuthController;
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

    Route::middleware(['auth'])->group(function(){

        Route::get("/profile", [AuthController::class, "profile"]);
        Route::post("/logout", [AuthController::class, "logout"]);
    });

});




Route::get('no-autorizado', function(){
    return ["mensaje" => "No estas permitido para ver esta pagina"];
})->name('login');

