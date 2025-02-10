<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $rules = [
        'required' => 'El campo :attribute es requerido',
        'string' => 'El campo :attribute debe ser una cadena',
        'max' => 'El campo :attribute no puede tener más de :max caracteres',
        'min' => 'El campo :attribute debe tener al menos :min caracteres',
    ];

    public function login(Request $request)
    {
        // Validar las entradas del formulario
        $validator = Validator::make($request->all(), [
            'user' => 'required|string|max:255',
            'password' => 'required|string|min:1',
        ], $this->rules);

        // Si la validación falla, devolver errores
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        // Buscar el usuario por el campo 'user'
        $user = User::where('user', $request->user)->first();

        // Verificar si el usuario existe
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Verificar si la contraseña es correcta
        if ($request->password !== $user->password) {
            return response()->json(['message' => 'Contraseña incorrecta'], 401);
        }

        try {
            // Crear un token para el usuario autenticado
            $token = $user->createToken('Laravel Password Grant Client')->accessToken;

            return response()->json(['access_token' => $token, 'user' => $user, 'token_type' => 'Bearer']);
        } catch (\Exception $e) {
            Log::alert($e->getMessage());

            // Si hay un error al crear el token, devolver mensaje de error
            return response()->json(['message' => 'Error al crear el token', 'error' => $e->getMessage()], 500);
        }
    }

    public function home()
    {
        return response()->json(['message' => 'Welcome to the home page'], 200);
    }
}
