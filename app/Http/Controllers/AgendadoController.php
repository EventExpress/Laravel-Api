<?php

namespace App\Http\Controllers;

use App\Models\Comprovante;
use App\Models\Agendado;
use App\Models\Servico;
use App\Models\Anuncio;
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

        if ($user->typeUsers->first()->tipousu !== 'Locatario') {
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

        if ($user->typeUsers->first()->tipousu !== 'Locatario') {
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

    public function store(Request $request, $anuncio_id)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validate([
                'servicoId' => 'nullable|array',
                'formapagamento' => 'required|string|max:50',
                'data_inicio' => 'required|date',
                'data_fim' => 'required|date|after_or_equal:data_inicio',
            ]);

            $dataInicio = $validatedData['data_inicio'];
            $dataFim = $validatedData['data_fim'];

            $anuncio = Anuncio::findOrFail($anuncio_id);
            $agenda = json_decode($anuncio->agenda, true) ?? [];

            $datasIndisponiveis = collect($agenda)->map(function ($data) {
                return date('Y-m-d', strtotime($data));
            });

            if ($datasIndisponiveis->contains($dataInicio) || $datasIndisponiveis->contains($dataFim)) {
                return response()->json([
                    'status' => false,
                    'message' => 'As datas selecionadas estão indisponíveis para reserva.',
                ], 422);
            }

            // calcula a quantidade de dias da locação
            $quantidadeDias = (new \DateTime($dataInicio))->diff(new \DateTime($dataFim))->days + 1; // +1 para incluir o último dia

            // calcula o valor total
            $valorAnuncio = $anuncio->valor;
            $valorTotalAnuncio = $valorAnuncio * $quantidadeDias;

            // soma valor dos serviços
            $valorTotalServicos = 0;
            if ($request->has('servicoId') && is_array($request->servicoId)) {
                foreach ($request->servicoId as $servicoId) {
                    $servico = Servico::find($servicoId);
                    if ($servico) {
                        $valorTotalServicos += $servico->valor * $quantidadeDias;
                    }
                }
            }


            $valorTotal = $valorTotalAnuncio + $valorTotalServicos;

            $conflict = Agendado::where('anuncio_id', $anuncio_id)
                ->where(function ($query) use ($dataInicio, $dataFim) {
                    $query->where('data_inicio', '<=', $dataFim)
                        ->where('data_fim', '>=', $dataInicio);
                })
                ->exists();

            if ($conflict) {
                Log::channel('logagendados')->warning('Conflito ao criar agendado', [
                    'user_id' => Auth::id(),
                    'anuncio_id' => $anuncio_id,
                    'data_inicio' => $dataInicio,
                    'data_fim' => $dataFim,
                    'occurred_at' => now(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Este anúncio já está reservado para as datas selecionadas.',
                ], 409);
            }
            Log::channel('logagendados')->info('Valor total calculado', [
                'valor_total_anuncio' => $valorTotalAnuncio,
                'valor_total_servicos' => $valorTotalServicos,
                'valor_total' => $valorTotal,
            ]);


            $agendado = new Agendado();
            $agendado->user_id = Auth::id();
            $agendado->anuncio_id = $anuncio_id;
            $agendado->formapagamento = $validatedData['formapagamento'];
            $agendado->data_inicio = $dataInicio;
            $agendado->data_fim = $dataFim;
            $agendado->valor_total = $valorTotal;
            $agendado->save();

            if ($request->has('servicoId') && is_array($request->servicoId)) {
                $agendado->servico()->attach($validatedData['servicoId']);
            }

            $this->createComprovante($agendado);

            DB::commit();

            Log::channel('logagendados')->info('Reserva criada com sucesso', [
                'agendado_id' => $agendado->id,
                'user_id' => Auth::id(),
                'created_at' => now(),
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'valor_total' => $valorTotal,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Reserva criada com sucesso.',
                'agendado' => $agendado,
                'valor_total' => $valorTotal,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Erro ao criar a reserva: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function createComprovante(Agendado $agendado)
    {
        $servicoId = $agendado->servico->first()->id ?? null;

        return Comprovante::create([
            'user_id' => $agendado->user_id,
            'anuncios_id' => $agendado->anuncio_id,
            'servicos_id' => $servicoId,
        ]);
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
                'servicoId' => 'nullable|array',
                'formapagamento' => 'sometimes|required|string|max:50',
                'data_inicio' => 'sometimes|required|date',
                'data_fim' => 'sometimes|required|date|after_or_equal:data_inicio',
            ]);

            $user = Auth::user();
            $agendado = Agendado::findOrFail($id);

            if ($agendado->user_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'error' => 'Você não tem permissão para editar esta reserva.',
                ], 403);
            }

            $anuncio = Anuncio::findOrFail($agendado->anuncio_id);
            $agenda = json_decode($anuncio->agenda, true) ?? [];
            $datasIndisponiveis = collect($agenda)->map(fn($data) => date('Y-m-d', strtotime($data)));

            if (isset($validatedData['data_inicio']) && $datasIndisponiveis->contains(date('Y-m-d', strtotime($validatedData['data_inicio'])))) {
                return response()->json([
                    'status' => false,
                    'message' => 'A nova data de início está indisponível para reserva.',
                ], 422);
            }

            if (isset($validatedData['data_fim']) && $datasIndisponiveis->contains(date('Y-m-d', strtotime($validatedData['data_fim'])))) {
                return response()->json([
                    'status' => false,
                    'message' => 'A nova data de fim está indisponível para reserva.',
                ], 422);
            }

            if (isset($validatedData['formapagamento'])) {
                $agendado->formapagamento = $validatedData['formapagamento'];
            }
            if (isset($validatedData['data_inicio'])) {
                $agendado->data_inicio = $validatedData['data_inicio'];
            }
            if (isset($validatedData['data_fim'])) {
                $agendado->data_fim = $validatedData['data_fim'];
            }

            $quantidadeDias = (new \DateTime($agendado->data_inicio))->diff(new \DateTime($agendado->data_fim))->days + 1; // +1 para incluir o último dia
            $valorAnuncio = $anuncio->valor;
            $valorTotalAnuncio = $valorAnuncio * $quantidadeDias;

            $valorTotalServicos = 0;
            if ($request->has('servicoId') && is_array($request->servicoId)) {
                foreach ($request->servicoId as $servicoId) {
                    $servico = Servico::find($servicoId);
                    if ($servico) {
                        $valorTotalServicos += $servico->valor;
                    }
                }
            }

            $valorTotal = $valorTotalAnuncio + $valorTotalServicos;
            $agendado->valor_total = $valorTotal;

            $agendado->save();

            if ($request->has('servicoId') && is_array($request->servicoId)) {
                $agendado->servico()->sync($validatedData['servicoId']);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Reserva atualizada com sucesso.',
                'agendado' => $agendado,
                'valor_total' => $valorTotal,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
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

        $dataAtual = now();
        $dataInicio = $agendado->data_inicio;

        if ($dataAtual->diffInDays($dataInicio, false) < 3) {
            return response()->json([
                'status' => false,
                'error' => 'Você só pode cancelar esta reserva até 3 dias antes da data de início.'
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


