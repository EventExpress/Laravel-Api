<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Agendado;
use App\Models\Servico;
use App\Models\Anuncio;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgendadoController extends Controller
{
    public function index()
    {
        $agendados = Agendado::with(['user', 'anuncio', 'servico'])->get();

        Log::channel('logagendados')->info('Acessou o índice de agendados', [
            'agendados_count' => $agendados->count(),
            'accessed_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'agendados' => $agendados,
        ], 200);
    }

    public function meusAgendados()
    {
        $user = Auth::user();

        if ($user->typeUsers->first()->tipousu !== 'locatario') {
            Log::channel('logagendados')->warning('Acesso negado a meus agendados', [
                'user_id' => $user->id,
                'reason' => 'Permissão negada',
                'accessed_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'error' => 'Você não tem permissão para reservar.'
            ], 403);
        }

        $user_id = $user->id;
        $agendados = Agendado::where('user_id', $user_id)->get();

        Log::channel('logagendados')->info('Acessou meus agendados', [
            'user_id' => $user_id,
            'agendados_count' => $agendados->count(),
            'accessed_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'agendados' => $agendados,
        ], 200);
    }

    public function create()
    {
        $user = Auth::user();

        if ($user->typeUsers->first()->tipousu !== 'locatario') {
            Log::channel('logagendados')->warning('Acesso negado ao criar agendado', [
                'user_id' => $user->id,
                'reason' => 'Permissão negada',
                'accessed_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'error' => 'Você não tem permissão para reservar.'
            ], 403);
        }

        $anuncios = Anuncio::all();
        $servicos = Servico::all();

        Log::channel('logagendados')->info('Acessou o método de criação de agendado', [
            'user_id' => $user->id,
            'accessed_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'user' => $user,
            'anuncios' => $anuncios,
            'servicos' => $servicos,
        ], 200);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validate([
                'anuncio_id' => 'required|exists:anuncios,id',
                'servicoId' => 'nullable|array',
                'formapagamento' => 'required|string|max:50',
                'data_inicio' => 'required|date',
                'data_fim' => 'required|date|after_or_equal:data_inicio',
            ]);

            $dataInicio = $validatedData['data_inicio'];
            $dataFim = $validatedData['data_fim'];

            $conflict = Agendado::where('anuncio_id', $validatedData['anuncio_id'])
                ->where(function ($query) use ($dataInicio, $dataFim) {
                    $query->where('data_inicio', '<=', $dataFim)
                        ->where('data_fim', '>=', $dataInicio);
                })
                ->exists();

            if ($conflict) {
                Log::channel('logagendados')->warning('Conflito ao criar agendado', [
                    'user_id' => Auth::id(),
                    'anuncio_id' => $validatedData['anuncio_id'],
                    'data_inicio' => $dataInicio,
                    'data_fim' => $dataFim,
                    'occurred_at' => now(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Este anúncio já está reservado para as datas selecionadas.',
                ], 409);
            }

            $agendado = new Agendado();
            $agendado->user_id = Auth::id();
            $agendado->anuncio_id = $validatedData['anuncio_id'];
            $agendado->formapagamento = $validatedData['formapagamento'];
            $agendado->data_inicio = $validatedData['data_inicio'];
            $agendado->data_fim = $validatedData['data_fim'];
            $agendado->save();

            if ($request->has('servicoId') && is_array($request->servicoId)) {
                $validServicoIds = array_filter($request->servicoId, function ($id) {
                    return !is_null($id) && is_numeric($id);
                });

                if (!empty($validServicoIds)) {
                    $agendado->servico()->attach($validServicoIds);
                }
            }

            DB::commit();

            Log::channel('logagendados')->info('Reserva criada com sucesso', [
                'agendado_id' => $agendado->id,
                'user_id' => Auth::id(),
                'created_at' => now(),
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Reserva criada com sucesso.',
                'agendado' => $agendado,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('logagendados')->error('Falha ao criar reserva', [
                'error_message' => $e->getMessage(),
                'request_data' => $request->all(),
                'occurred_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erro ao criar a reserva: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request)
    {
        $search = $request->input('search');

        $agendados = Agendado::Where('formapagamento', 'like', "%$search%")
            ->orWhere('data_inicio', 'like', "%$search%")
            ->orWhere('data_fim', 'like', "%$search%")
            ->orWhereHas('user', function ($query) use ($search) {
                $query->where('nome', 'like', "%$search%");
            })->get();

        Log::channel('logagendados')->info('Buscou agendados', [
            'search' => $search,
            'results_count' => $agendados->count(),
            'searched_at' => now(),
        ]);

        if ($agendados->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Reserva não encontrada.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'results' => $agendados,
        ], 200);
    }

    public function edit($id)
    {
        $agendado = Agendado::find($id);
        $user = Auth::user();

        if (!$agendado || $agendado->user_id != $user->id) {
            Log::channel('logagendados')->warning('Acesso negado ao editar agendado', [
                'user_id' => $user->id,
                'agendado_id' => $id,
                'reason' => 'Reserva não encontrada ou permissão negada',
                'accessed_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'error' => 'Reserva não encontrada ou você não tem permissão para editá-lo.'
            ], 403);
        }

        $servicos = Servico::all();
        $servicoSelecionado = $agendado->servico->pluck('id')->toArray();

        Log::channel('logagendados')->info('Acessou o método de edição de agendado', [
            'user_id' => $user->id,
            'agendado_id' => $id,
            'accessed_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'agendado' => $agendado,
            'servicos' => $servicos,
            'servicoSelecionado' => $servicoSelecionado,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validate([
                'anuncio_id' => 'required|exists:anuncios,id',
                'servicoId' => 'nullable|array',
                'formapagamento' => 'required|string|max:50',
                'data_inicio' => 'required|date',
                'data_fim' => 'required|date|after_or_equal:data_inicio',
            ]);

            $user = Auth::user();
            $agendado = Agendado::find($id);

            if (!$agendado || $agendado->user_id != $user->id) {
                Log::channel('logagendados')->warning('Acesso negado ao atualizar agendado', [
                    'user_id' => $user->id,
                    'agendado_id' => $id,
                    'reason' => 'Reserva não encontrada ou permissão negada',
                    'accessed_at' => now(),
                ]);

                return response()->json([
                    'status' => false,
                    'error' => 'Reserva não encontrada ou você não tem permissão para editá-lo.'
                ], 403);
            }

            $dataInicio = $validatedData['data_inicio'];
            $dataFim = $validatedData['data_fim'];

            $conflict = Agendado::where('anuncio_id', $validatedData['anuncio_id'])
                ->where('id', '!=', $id)
                ->where(function ($query) use ($dataInicio, $dataFim) {
                    $query->where('data_inicio', '<=', $dataFim)
                        ->where('data_fim', '>=', $dataInicio);
                })
                ->exists();

            if ($conflict) {
                Log::channel('logagendados')->warning('Conflito ao atualizar agendado', [
                    'user_id' => $user->id,
                    'agendado_id' => $id,
                    'anuncio_id' => $validatedData['anuncio_id'],
                    'data_inicio' => $dataInicio,
                    'data_fim' => $dataFim,
                    'occurred_at' => now(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Este anúncio já está reservado para as datas selecionadas.',
                ], 409);
            }

            $agendado->anuncio_id = $validatedData['anuncio_id'];
            $agendado->formapagamento = $validatedData['formapagamento'];
            $agendado->data_inicio = $validatedData['data_inicio'];
            $agendado->data_fim = $validatedData['data_fim'];
            $agendado->save();

            if ($request->has('servicoId') && is_array($request->servicoId)) {
                $validServicoIds = array_filter($request->servicoId, function ($id) {
                    return !is_null($id) && is_numeric($id);
                });

                $agendado->servico()->sync($validServicoIds);
            }

            DB::commit();

            Log::channel('logagendados')->info('Reserva atualizada com sucesso', [
                'agendado_id' => $agendado->id,
                'user_id' => $user->id,
                'updated_at' => now(),
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Reserva atualizada com sucesso.',
                'agendado' => $agendado,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('logagendados')->error('Falha ao atualizar reserva', [
                'error_message' => $e->getMessage(),
                'request_data' => $request->all(),
                'occurred_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erro ao atualizar a reserva: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $agendado = Agendado::find($id);

        if (!$agendado || $agendado->user_id != $user->id) {
            Log::channel('logagendados')->warning('Acesso negado ao excluir agendado', [
                'user_id' => $user->id,
                'agendado_id' => $id,
                'reason' => 'Reserva não encontrada ou permissão negada',
                'accessed_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'error' => 'Reserva não encontrada ou você não tem permissão para excluí-la.'
            ], 403);
        }

        $agendado->delete();

        Log::channel('logagendados')->info('Reserva excluída com sucesso', [
            'agendado_id' => $id,
            'user_id' => $user->id,
            'deleted_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Reserva excluída com sucesso.',
        ], 200);
    }
}
