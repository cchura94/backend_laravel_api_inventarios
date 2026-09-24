<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login(Request $request){
        // Validar
        $credenciales = $request->validate([
            "email" => "required|email|min:5|max:200",
            "password" => ["required", Password::min(6)       // Mínimo 8 caracteres
                                                ->mixedCase()      // Al menos una mayúscula y una minúscula
                                                ->letters()        // Al menos una letra
                                                ->numbers()        // Al menos un número
                                                ->symbols()        // Al menos un carácter especial (!@#$%^&*)
                                                ]
        ]);

        // autenticar
        if(!Auth::attempt($credenciales)){
            return response()->json([
                "mensaje" => "Credenciales Incorrectas"
            ], 401);
        }

        // generar token
        $token = $request->user()->createToken("TokenAuth")->plainTextToken;

        // responder el usuario + token
        return response()->json([
            "access_token" => $token,
            "usuario" => $request->user()
        ]);
    }

    public function register(Request $request){
        // validacion
        // registro
        // respuesta
    }

    public function profile(Request $request){

    }

    public function logout(Request $request){

    }
}
