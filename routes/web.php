<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get("/saludo", function(){
    return "Hola saludos desde web.php";
});
