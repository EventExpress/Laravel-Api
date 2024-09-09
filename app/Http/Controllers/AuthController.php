<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Exception;

class AuthController extends Controller
{
    /**
     * Handle user login and return a token.
     */
    public function login(Request $request) : JsonResponse
    {
        try {
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
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred during login',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle user logout and remove token.
     */
    public function logout(Request $request) : JsonResponse
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'message' => 'No user is authenticated.',
                ], 401);
            }

            $user = Auth::user();
            $token = $request->bearerToken();

            if ($token) {
                $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();

                return response()->json([
                    'message' => 'Token removed successfully and User disconnected.',
                ], 200);
            }

            return response()->json([
                'message' => 'Token not found.',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred during logout',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the authenticated user's profile.
     */
    public function profile(Request $request) : JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated.',
                ], 401);
            }

            $user = $user->load(['nome', 'endereco', 'typeUsers']);

            return response()->json([
                'status' => true,
                'user' => $user,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving the user profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
