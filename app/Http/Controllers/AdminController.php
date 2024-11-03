<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{

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

    public function destroyUser($id): JsonResponse
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);
            $user->typeUsers()->detach();
            $user->delete();

            DB::commit();

            Log::channel('main')->info('User deleted by admin', [
                'user_id' => $id,
                'deleted_by' => Auth::id(),
                'deleted_at' => now(),
            ]);

            return response()->json(['message' => 'Usuário deletado com sucesso!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('main')->error('Failed to delete user by admin', [
                'user_id' => $id,
                'error_message' => $e->getMessage(),
                'occurred_at' => now(),
            ]);

            return response()->json(['message' => 'Erro ao deletar usuário'], 500);
        }
    }

    public function destroyServico($id): JsonResponse
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $servico = Servico::find($id);

        if (!$servico) {
            return response()->json(['message' => 'Serviço não encontrado.'], 404);
        }

        DB::beginTransaction();

        try {
            $servico->delete();
            DB::commit();

            Log::channel('main')->info('Servico deleted by admin', [
                'servico_id' => $id,
                'deleted_by' => Auth::id(),
                'deleted_at' => now(),
            ]);

            return response()->json(['message' => 'Serviço deletado com sucesso!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('main')->error('Failed to delete servico by admin', [
                'servico_id' => $id,
                'error_message' => $e->getMessage(),
                'occurred_at' => now(),
            ]);

            return response()->json(['message' => 'Erro ao deletar serviço'], 500);
        }
    }

    public function destroyAnuncio($id): JsonResponse
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $anuncio = Anuncio::find($id);

        if (!$anuncio) {
            return response()->json(['message' => 'Anúncio não encontrado.'], 404);
        }

        DB::beginTransaction();

        try {
            $anuncio->endereco()->delete();
            $anuncio->delete();

            DB::commit();

            Log::channel('loganuncios')->info('Anuncio deleted by admin', [
                'anuncio_id' => $id,
                'deleted_by' => Auth::id(),
                'deleted_at' => now(),
            ]);

            return response()->json(['message' => 'Anúncio deletado com sucesso!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('loganuncios')->error('Failed to delete anuncio by admin', [
                'anuncio_id' => $id,
                'error_message' => $e->getMessage(),
                'occurred_at' => now(),
            ]);

            return response()->json(['message' => 'Erro ao deletar anúncio'], 500);
        }
    }

    private function isAdmin(): bool
    {
        $user = Auth::user();
        return Auth::check() && $user->typeUsers->contains('tipousu', 'admin');
    }

}
