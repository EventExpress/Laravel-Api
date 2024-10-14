<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Agendado;
use App\Models\Servico;
use App\Models\Anuncio;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgendadoController extends Controller
{
    public function index()
    {
        $agendados = Agendado::all();

        //$agendados = Agendado::with(['user', 'anuncio', 'servico'])->get();

        return response()->json([
            'status' => true,
            'agendados' => $agendados,
        ], 200);
    }

    public function meusAgendados()
    {
        $user = Auth::user();

        if ($user->typeUsers->first()->tipousu !== 'locatario') {
            return response()->json([
                'status' => false,
                'error' => 'Você não tem permissão para reservar.'
            ], 403);
        }

        $user_id = $user->id;
        $agendados = Agendado::where('user_id', $user_id)->get();

        return response()->json([
            'status' => true,
            'agendados' => $agendados,
        ], 200);
    }

    public function create()
    {
        $user = Auth::user();

        if ($user->typeUsers->first()->tipousu !== 'locatario') {
            return response()->json([
                'status' => false,
                'error' => 'Você não tem permissão para reservar.'
            ], 403);
        }

        $anuncios = Anuncio::all();
        $servicos = Servico::all();
        return response()->json([
            'status' => true,
            'user' => $user,
            'anuncios' => $anuncios,
            'servicos' => $servicos,
        ], 200);
    }

    public function store(Request $request)
{
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

    if (!$agendado) {
        return response()->json([
            'status' => false,
            'message' => 'Erro ao reservar',
        ], 500);
    }

    if ($request->has('servicoId') && is_array($request->servicoId)) {
        $validServicoIds = array_filter($request->servicoId, function ($id) {
            return !is_null($id) && is_numeric($id);
        });

        if (!empty($validServicoIds)) {
            $agendado->servicos()->attach($validServicoIds);
        }
    }

    return response()->json([
        'status' => true,
        'message' => 'Reserva criada com sucesso.',
        'agendado' => $agendado,
    ], 201);

    }

    public function show(Request $request)
    {
        $search = $request->input('search');

            $servico = Servico::Where('formapagamento', 'like', "%$search%")
                ->orWhere('data_inicio','like',"%$search%")
                ->orWhere('data_fim', 'like', "%$search%")
                ->orWhereHas('user', function ($query) use ($search) {
                    $query->where('nome', 'like', "%$search%");
                })->get();
        
        if (!$agendado) {
            return response()->json([
                'status' => false,
                'message' => 'Reserva não encontrada.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'results' => $results,
        ], 200);
    }

    public function edit($id)
    {
        $agendado = Agendado::find($id);
        $user = Auth::user();

        if (!$agendado || $agendado->user_id != $user->id) {
            return response()->json([
                'status' => false,
                'error' => 'Reserva não encontrada ou você não tem permissão para edita-lo.'
            ], 403);
        }
        $servicos = Servico::all();
        $servicoSelecionado = $agendado->servico->pluck('id')->toArray();

        return response()->json([
            'status' => true,
            'agendado' => $agendado,
            'servicos' => $servicos,
            'servicoSelecionado' => $servicoSelecionado,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'anuncio_id' => 'required|exists:anuncios,id',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'servicoId' => 'required|array'
        ]);

        $user = Auth::user();
        $agendado = Agendado::find($id);

        if (!$agendado || $agendado->user_id != $user->id) {
            return response()->json([
                'status' => false,
                'error' => 'Reserva não encontrada ou você não tem permissão para editá-la.'
            ], 403);
        }

        $dataInicio = $validatedData['data_inicio'];
        $dataFim = $validatedData['data_fim'];

        // Verifica se há conflitos de data para o mesmo anúncio
        $conflict = Agendado::where('anuncio_id', $validatedData['anuncio_id'])
            ->where('id', '!=', $agendado->id) // Exclui a própria reserva do conflito
            ->where(function ($query) use ($dataInicio, $dataFim) {
                $query->where('data_inicio', '<=', $dataFim)
                    ->where('data_fim', '>=', $dataInicio);
        })
        ->exists();

        if ($conflict) {
            return response()->json([
                'status' => false,
                'message' => 'Este anúncio já está reservado para as datas selecionadas.',
            ], 409);
        }

        $agendado->data_inicio = $dataInicio;
        $agendado->data_fim = $dataFim;
        $agendado->save();

        $agendado->update([
            'anuncio_id' => $validatedData['anuncio_id'],
            'data_inicio' => $validatedData['data_inicio'],
            'data_fim' => $validatedData['data_fim'],
        ]);

        $agendado->servicos()->sync($validatedData['servicoId']);

        return response()->json([
            'status' => true,
            'message' => 'Reserva atualizado com sucesso.',
            'agendado' => $agendado,
        ], 200);
    }

    public function destroy($id)
    {
        $agendado = Agendado::find($id);
        $user = Auth::user();

        if (!$agendado || $agendado->user_id != $user->id) {
            return response()->json([
                'status' => false,
                'error' => 'Reserva não encontrada ou você não tem permissão para excluí-la.'
            ], 403);
        }

        $agendado->delete();

        return response()->json([
            'status' => true,
            'message' => 'Reserva cancelada com sucesso.',
        ], 200);
    }

}

