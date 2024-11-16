<?php

namespace App\Http\Controllers;

use App\Models\Avaliacao;
use App\Models\Comprovante;
use App\Models\Agendado;
use App\Models\Servico;
use App\Models\Anuncio;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $agendados = Agendado::where('user_id', $user_id)
                            ->where('data_inicio', '>=', now())
                            ->get();

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

    public function meusAnunciosAgendados()
    {
        $user = Auth::user();

        if (!$user->typeUsers->contains('tipousu', 'Locador')) {
            Log::channel('logagendados')->warning('Acesso negado a anúncios agendados', [
                'user_id' => $user->id,
                'reason' => 'Permissão negada',
                'accessed_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'error' => 'Você não tem permissão para acessar anúncios agendados.'
            ], 403);
        }

        $anunciosAgendados = Agendado::whereHas('anuncio', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('anuncio', 'user')->get();

        return response()->json([
            'status' => true,
            'anuncios_agendados' => $anunciosAgendados,
        ], 200);
    }

    public function meusServicosAgendados()
    {
        $user = Auth::user();

        if (!$user->typeUsers->contains('tipousu', 'Prestador')) {
            Log::channel('logagendados')->warning('Acesso negado a serviços agendados', [
                'user_id' => $user->id,
                'reason' => 'Permissão negada',
                'accessed_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'error' => 'Você não tem permissão para acessar serviços agendados.'
            ], 403);
        }

        $servicosAgendados = Agendado::whereHas('servicos', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('servicos', 'user')->get();

        return response()->json([
            'status' => true,
            'servicos_agendados' => $servicosAgendados,
        ], 200);
    }

    public function meusHistoricoAgendados()
    {
        $user = Auth::user();

        if ($user->typeUsers->first()->tipousu !== 'Locatario') {
            Log::channel('logagendados')->warning('Acesso negado ao histórico de agendamentos', [
                'user_id' => $user->id,
                'reason' => 'Permissão negada - apenas locatários podem acessar',
                'accessed_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'error' => 'Acesso negado. Apenas locatários podem visualizar o histórico de agendamentos.'
            ], 403);
        }

        $dataAtual = Carbon::now()->toDateString();

        $agendadosHistorico = Agendado::where('user_id', $user->id)
            ->where('data_fim', '<', $dataAtual)
            ->with(['anuncio', 'servicos'])
            ->get();

        if ($agendadosHistorico->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Nenhuma reserva encontrada no histórico.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'historico_agendados' => $agendadosHistorico,
        ], 200);
    }

    public function avaliarComoLocatario(Request $request, $agendadoId)
    {
        $user = auth()->user();
        $agendado = Agendado::findOrFail($agendadoId);

        if ($agendado->user_id !== $user->id) {
            return response()->json(['error' => 'Você não tem permissão para avaliar este agendamento.'], 403);
        }

        if (now() <= $agendado->data_fim) {
            return response()->json(['error' => 'A avaliação só pode ser feita após o término da reserva.'], 403);
        }

        $request->validate([
            'avaliacao_anuncio.nota' => 'required|in:1,2,3,4,5',
            'avaliacao_anuncio.comentario' => 'nullable|string',
            'avaliacao_servico.*.servico_id' => 'required|exists:servicos,id',
            'avaliacao_servico.*.nota' => 'required|in:1,2,3,4,5',
            'avaliacao_servico.*.comentario' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            if ($agendado->anuncio_id) {
                $agendado->anuncio->avaliacoes()->create([
                    'user_id' => $user->id,
                    'nota' => $request->avaliacao_anuncio['nota'],
                    'comentario' => $request->avaliacao_anuncio['comentario'],
                ]);
            }

            if ($agendado->servicos) {
                foreach ($agendado->servicos as $servico) {
                    $avaliacaoServicos = collect($request->avaliacao_servico)->firstWhere('servico_id', $servico->id);

                    if ($avaliacaoServicos) {
                        $servico->avaliacoes()->create([
                            'user_id' => $user->id,
                            'nota' => $avaliacaoServicos['nota'],
                            'comentario' => $avaliacaoServicos['comentario'],
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => 'Avaliação realizada com sucesso!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ocorreu um erro ao realizar a avaliação.'], 500);
        }
    }

    public function avaliarComoLocadorOuPrestador(Request $request, $agendadoId)
    {
        $user = auth()->user();
        $agendado = Agendado::findOrFail($agendadoId);

        $isLocador = $agendado->anuncio && $agendado->anuncio->user_id === $user->id;
        $isPrestador = $agendado->servicos && $agendado->servicos->pluck('user_id')->contains($user->id);

        if (!$isLocador && !$isPrestador) {
            return response()->json(['error' => 'Você não tem permissão para avaliar este agendamento.'], 403);
        }

        if (now() <= $agendado->data_fim) {
            return response()->json(['error' => 'A avaliação só pode ser feita após o término da reserva.'], 403);
        }

        $request->validate([
            'avaliacao_locatario.nota' => 'required|in:1,2,3,4,5',
            'avaliacao_locatario.comentario' => 'nullable|string',
            'avaliacao_locatario.user_id' => 'required|exists:users,id',
        ]);

        if ($request->avaliacao_locatario['user_id'] !== $user->id) {
            return response()->json(['error' => 'Você não pode avaliar o locatário como outro usuário.'], 403);
        }

        DB::beginTransaction();

        try {
            $agendado->user->avaliacoes()->create([
                'user_id' => $user->id,
                'nota' => $request->avaliacao_locatario['nota'],
                'comentario' => $request->avaliacao_locatario['comentario'],
            ]);

            DB::commit();

            return response()->json(['message' => 'Avaliação realizada com sucesso!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ocorreu um erro ao realizar a avaliação.'], 500);
        }
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
            $validatedData = $this->validateRequest($request);

            list($diasReservados, $inicio, $fim) = $this->calculateDays($validatedData);

            $this->checkReservationConflict($anuncio_id, $request->data_inicio, $request->data_fim);

            $this->checkUnavailableDates($anuncio_id, $inicio, $fim);

            $valorTotal = $this->calculateTotalValue($validatedData, $diasReservados, $anuncio_id);

            $agendado = $this->createAgendado($validatedData, $anuncio_id, $valorTotal);

            $this->attachServices($agendado, $request->servicos_data ?? []);

            $this->createComprovante($agendado, $validatedData['servicoId'] ?? []);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Reserva criada com sucesso.',
                'agendado' => $agendado,
                'valor_total' => $valorTotal,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('logagendados')->error('Erro ao criar a reserva', [
                'message' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Erro ao criar a reserva: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function validateRequest(Request $request)
    {
        return $request->validate([
            'servicoId' => 'array',
            'servicoId.*' => 'integer|exists:servicos,id',
            'formapagamento' => 'required|string',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'servicos_data' => 'required|array',
            'servicos_data.*.id' => 'required|integer|exists:servicos,id',
            'servicos_data.*.data_inicio' => 'required|date',
            'servicos_data.*.data_fim' => 'required|date|after_or_equal:servicos_data.*.data_inicio',
        ]);
    }

    protected function calculateDays(array $validatedData): array
    {
        $inicio = $validatedData['data_inicio'];
        $fim = $validatedData['data_fim'];
        $diasReservados = (new \DateTime($inicio))->diff(new \DateTime($fim))->days + 1; // +1 para incluir o último dia

        return [$diasReservados, $inicio, $fim];
    }

    protected function checkReservationConflict($anuncio_id, $inicio, $fim)
    {
        $agendadoCancelado = Agendado::where('anuncio_id', $anuncio_id)
            ->where('status_pagamento', 'cancelado')
            ->first();

        if (!$agendadoCancelado) {
            $conflict = Agendado::where('anuncio_id', $anuncio_id)
                ->where(function ($query) use ($inicio, $fim) {
                    $query->where('data_inicio', '<=', $fim)
                        ->where('data_fim', '>=', $inicio);
                })
                ->where('status_pagamento', '!=', 'cancelado')
                ->exists();

            if ($conflict) {
                throw new \Exception('Este anúncio já está reservado para as datas selecionadas.', 409);
            }
        }
    }

    protected function checkUnavailableDates($anuncio_id, $inicio, $fim)
    {
        $anuncio = Anuncio::findOrFail($anuncio_id);

        $agenda = json_decode($anuncio->agenda, true) ?? [];

        $datasIndisponiveis = collect($agenda)->map(function ($data) {
            return date('Y-m-d', strtotime($data));
        });

        $inicio = date('Y-m-d', strtotime($inicio));
        $fim = date('Y-m-d', strtotime($fim));

        if ($datasIndisponiveis->contains($inicio) || $datasIndisponiveis->contains($fim)) {
            throw new \Exception('As datas selecionadas estão indisponíveis para reserva.', 422);
        }
    }

    protected function calculateTotalValue(array $validatedData, int $diasReservados, $anuncio_id)
    {
        $anuncio = Anuncio::findOrFail($anuncio_id);
        $valorAnuncio = $anuncio->valor;

        $valorTotalAnuncio = $valorAnuncio * $diasReservados;

        $valorTotalServicos = 0;

        if (isset($validatedData['servicoId']) && is_array($validatedData['servicoId'])) {
            foreach ($validatedData['servicoId'] as $index => $servicoId) {
                $servico = Servico::find($servicoId);

                if ($servico) {
                    $servicoData = isset($validatedData['servicos_data'][$index]) ? $validatedData['servicos_data'][$index] : null;

                    if ($servicoData) {
                        $dataInicio = new \Carbon\Carbon($servicoData['data_inicio']);
                        $dataFim = new \Carbon\Carbon($servicoData['data_fim']);

                        // Ajusta as datas para considerar apenas o dia (sem hora)
                        $dataInicio->startOfDay();
                        $dataFim->startOfDay();

                        $diasServico = $dataInicio->diffInDays($dataFim) + 1;


                        $valorTotalServicos += $servico->valor * $diasServico;
                    }
                }
            }
        }

        $valorFinal = $valorTotalAnuncio + $valorTotalServicos;

        return $valorFinal;
    }

    protected function createAgendado(array $validatedData, $anuncio_id, $valorTotal)
    {
        $agendado = new Agendado();
        $agendado->user_id = Auth::id();
        $agendado->anuncio_id = $anuncio_id;
        $agendado->formapagamento = $validatedData['formapagamento'];
        $agendado->data_inicio = $validatedData['data_inicio'];
        $agendado->data_fim = $validatedData['data_fim'];
        $agendado->valor_total = $valorTotal;
        $agendado->save();

        return $agendado;
    }

    protected function attachServices(Agendado $agendado, array $servicosData)
    {
        if (empty($servicosData)) {
            return;
        }

        $attachData = [];
        foreach ($servicosData as $servicoData) {
            if (isset($servicoData['id'], $servicoData['data_inicio'], $servicoData['data_fim'])) {
                $attachData[$servicoData['id']] = [
                    'data_inicio' => $servicoData['data_inicio'],
                    'data_fim' => $servicoData['data_fim'],
                ];
            }
        }

        // Realiza o attach com os dados extras para a tabela pivot
        if (count($attachData) > 0) {
            $agendado->servicos()->attach($attachData);
        }
    }

    protected function createComprovante(Agendado $agendado, $servicoIds)
    {
        try {
            if (!is_array($servicoIds)) {
                throw new \InvalidArgumentException('O parâmetro $servicoIds deve ser um array.');
            }

            $comprovante = Comprovante::create([
                'user_id' => $agendado->user_id,
                'anuncios_id' => $agendado->anuncio_id,
                'servicos_id' => $servicoIds,
            ]);

            return $comprovante;
        } catch (\Exception $e) {
            \Log::error('Erro ao criar comprovante: ' . $e->getMessage() . ' | Serviço IDs: ' . json_encode($servicoIds));
            return null;
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
                'servicoId' => 'nullable|array',
                'formapagamento' => 'sometimes|required|string|max:50',
                'data_inicio' => 'sometimes|required|date',
                'data_fim' => 'sometimes|required|date|after_or_equal:data_inicio',
                'servicos_data' => 'sometimes|nullable|array',
                'servicos_data.*.id' => 'sometimes|nullable|exists:servicos,id',
                'servicos_data.*.data_inicio' => 'sometimes|nullable|date|after_or_equal:data_inicio',
                'servicos_data.*.data_fim' => 'sometimes|nullable|date|after_or_equal:servicos_data.*.data_inicio',
            ]);

            $user = Auth::user();
            $agendado = Agendado::findOrFail($id);

            if ($agendado->user_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'error' => 'Você não tem permissão para editar esta reserva.',
                ], 403);
            }

            if (array_key_exists('data_inicio', $validatedData)) {
                $agendado->data_inicio = $validatedData['data_inicio'];
            }
            if (array_key_exists('data_fim', $validatedData)) {
                $agendado->data_fim = $validatedData['data_fim'];
            }
            if (array_key_exists('formapagamento', $validatedData)) {
                $agendado->formapagamento = $validatedData['formapagamento'];
            }

            $dataInicio = $agendado->data_inicio;
            $dataFim = $agendado->data_fim;

            if ($request->has('servicoId') && is_array($request->servicoId)) {
                foreach ($request->servicoId as $servicoId) {
                    $servico = Servico::find($servicoId);

                    $agendaServico = json_decode($servico->agenda, true) ?? [];
                    $datasIndisponiveisServico = collect($agendaServico)->map(fn($data) => date('Y-m-d', strtotime($data)));

                    if ($datasIndisponiveisServico->contains($dataInicio) || $datasIndisponiveisServico->contains($dataFim)) {
                        return response()->json([
                            'status' => false,
                            'message' => "As datas selecionadas estão indisponíveis para o serviço.",
                        ], 422);
                    }
                }
            }

            $dataAtual = now();

            if ($dataAtual->diffInDays($agendado->data_inicio, false) < 3) {
                return response()->json([
                    'status' => false,
                    'error' => 'Você só pode editar esta reserva até 3 dias antes da data de início.',
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

            // Calcular a quantidade de dias reservados para o anúncio
            $quantidadeDias = (new \DateTime($agendado->data_inicio))->diff(new \DateTime($agendado->data_fim))->days + 1; // +1 para incluir o último dia
            $valorAnuncio = $anuncio->valor;
            $valorTotalAnuncio = $valorAnuncio * $quantidadeDias;

            // Calcular o valor total dos serviços adicionais
            $valorTotalServicos = 0;
            if (isset($validatedData['servicos_data']) && is_array($validatedData['servicos_data'])) {
                foreach ($validatedData['servicos_data'] as $servicoData) {
                    $servico = Servico::find($servicoData['id']);
                    if ($servico) {
                        // Calcular a quantidade de dias para cada serviço
                        $diasServicos = (new \DateTime($servicoData['data_inicio']))->diff(new \DateTime($servicoData['data_fim']))->days + 1; // +1 para incluir o último dia
                        $valorTotalServicos += $servico->valor * $diasServicos; // Adiciona o valor do serviço multiplicado pelos dias
                    }
                }
            }

            // Calcular o valor total
            $valorTotal = $valorTotalAnuncio + $valorTotalServicos;
            $agendado->valor_total = $valorTotal;

            $agendado->save();

            // Sincronizar os serviços contratados
            if (isset($validatedData['servicoId']) && is_array($validatedData['servicoId'])) {
                $agendado->servicos()->sync($validatedData['servicoId']);
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

    public function aprovarPagamento($id)
    {
        $agendado = Agendado::findOrFail($id);

        if ($this->mockPagamento($agendado)) {
            $agendado->status_pagamento = 'aprovado';
            $agendado->save();

            return response()->json(['message' => 'Pagamento aprovado com sucesso!']);
        }

        return response()->json(['message' => 'Falha na aprovação do pagamento.'], 400);
    }

    private function mockPagamento($agendado)
    {
        $statusPagamento = 'sucesso';

        \Log::channel('logagendados')->info("Pagamento para Agendado #{$agendado->id}: {$statusPagamento}");

        return $statusPagamento === 'sucesso';
    }


}
