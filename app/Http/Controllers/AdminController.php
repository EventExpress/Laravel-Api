<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{

    public function dashboard()
    {
        $user = Auth::user();

        // Verificar se o usuário autenticado tem o tipo de usuário 'admin'
        if (Auth::check() && $user->typeUsers->contains('tipousu', 'admin')) {
            return response()->json(['message' => 'Welcome to the admin dashboard!']);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function restore($id) : JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = User::withTrashed()->findOrFail($id); // Inclui registros excluídos

            $user->restore(); // Restaura o usuário

            DB::commit();

            return response()->json([
                'message' => 'Usuário restaurado com sucesso!',
                'user' => $user
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao restaurar usuário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
