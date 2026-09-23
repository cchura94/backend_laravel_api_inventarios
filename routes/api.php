<?php

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

    Route::post("/login", function(){
        return "Iniciado sesion...";
    });
    Route::post("/register", function(){
        return "Registrando...";
    });

    Route::middleware(['auth'])->group(function(){

        Route::get("/profile", function(){
            return "Mostrando mi Perfil...";
        });
        Route::post("/logout", function(){
            return "Cerrando sesion...";
        });
    });

});

Route::get('no-autorizado', function(){
    return ["mensaje" => "No estas permitido para ver esta pagina"];
})->name('login');

