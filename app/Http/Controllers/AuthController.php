<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user login and return a token.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Verifica se as credenciais estão corretas
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        // Remove todos os tokens antigos do usuário
        $user->tokens()->delete();

        // Cria um novo token para o usuário
        $token = $user->createToken('Personal Access Token after login')->plainTextToken;

        return response()->json([
            'message' => 'Authorized',
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {

        if (!Auth::check()) {
            return response()->json([
                'message' => 'No user is authenticated.',
            ], 401);
        }

        // Obtém o token atual da requisição
        $user = Auth::user();
        $token = $request->bearerToken();

        if ($token) {
            // Remove o token atual
            $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();

            return response()->json([
                'message' => 'Token removed successfully and User disconnected.',
            ], 200);
        }

        return response()->json([
            'message' => 'Token not found.',
        ], 404);
    }

}
