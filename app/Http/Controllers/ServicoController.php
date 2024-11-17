<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Agendado;
use App\Models\Anuncio;
use App\Models\Categoria;
use App\Models\Servico;
use App\Models\Scategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServicoController extends Controller
{
    public function index()
    {
        $servicos = Servico::all();

        return response()->json([
            'status' => true,
            'servicos' => $servicos,
        ], 200);
    }

    public function apresentaScategoriaServico()
    {
        $scategoria = Scategoria::all();
        return response()->json(['scategorias' => $scategoria], 200);
    }

    public function meusServicos()
    {
        $user = Auth::user();

        $types = $user->typeUsers->pluck('tipousu')->toArray();

        if (!in_array('Prestador', $types)) {
            return response()->json([
                'status' => false,
                'error' => 'Você não tem permissão para criar serviços.'
            ], 403);
        }

        $user_id = $user->id;
        $servicos = Servico::where('user_id', $user_id)->get();

        return response()->json([
            'status' => true,
            'servicos' => $servicos,
        ], 200);
    }


    public function create()
    {
        $user = Auth::user();

        if ($user->typeUsers->first()->tipousu !== 'prestador') {
            return response()->json([
                'status' => false,
                'error' => 'Você não tem permissão para criar serviços.'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'user' => $user,
        ], 200);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'cidade' => 'required|string|min:3|max:255',
            'bairro' => 'required|string|min:3|max:255',
            'descricao' => 'required|string|min:10|max:2000',
            'valor' => 'required|numeric|min:0',
            'scategoriaId' => 'required|array',
            'agenda' => 'nullable|array',
            'agenda.*' => 'date',
        ]);

        $servico = new Servico();
        $servico->user_id = Auth::id();
        $servico->cidade = $validatedData['cidade'];
        $servico->bairro = $validatedData['bairro'];
        $servico->descricao = $validatedData['descricao'];
        $servico->valor = $validatedData['valor'];
        $servico->agenda = $validatedData['agenda'];

        if (!empty($validatedData['agenda'])) {
            $servico->agenda = json_encode($validatedData['agenda']);
        } else {
            $servico->agenda = json_encode([]);
        }

        $servico->save();

        $servico->scategorias()->attach($validatedData['scategoriaId']);

        if (!$servico) {
            return response()->json([
                'status' => false,
                'message' => 'Erro ao criar serviço'
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Serviço criado com sucesso.',
            'servico' => $servico,
        ], 201);
    }

    public function show(Request $request)
    {
        $search = $request->input('search');

            $servico = Servico::where('valor','like',"%$search%")
                ->orwhere('cidade', 'like', "%$search%")
                ->orWhere('bairro', 'like', "%$search%")
                ->orWhere('descricao', 'like', "%$search%")
                ->orWhere('agenda', 'like', "%$search%")
                ->get();

        return response()->json([
            'status' => true,
            'results' => $servico,
        ], 200);
    }

    public function edit($id)
    {
        $servico = Servico::find($id);

        $user = Auth::user();

        if (!$servico || $servico->user_id != $user->id) {
            return response()->json([
                'status' => false,
                'error' => 'Serviço não encontrado ou você não tem permissão para editá-lo.'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'servico' => $servico,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'cidade' => 'sometimes|required|string|min:3|max:255',
            'bairro' => 'sometimes|required|string|min:3|max:255',
            'descricao' => 'sometimes|required|string|min:10|max:2000',
            'scategoriaId' => 'sometimes|required|array',
            'agenda' => 'nullable|array',
        ]);

        $user = Auth::user();
        $servico = Servico::find($id);

        if (!$servico || $servico->user_id != $user->id) {
            return response()->json([
                'status' => false,
                'error' => 'Serviço não encontrado ou você não tem permissão para editá-lo.'
            ], 403);
        }

        $servico->update(array_filter([
            'descricao' => $validatedData['descricao'] ?? null,
            'cidade' => $validatedData['cidade'] ?? null,
            'bairro' => $validatedData['bairro'] ?? null,
            'agenda' => $validatedData['agenda'] ?? null,
        ], function ($value) {
            return !is_null($value);
        }));

        if (isset($validatedData['scategoriaId'])) {
            $servico->scategorias()->sync($validatedData['scategoriaId']);
        }

        return response()->json([
            'status' => true,
            'message' => 'Serviço atualizado com sucesso.',
            'servico' => $servico,
        ], 200);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $servico = Servico::find($id);

        if (!$servico || $servico->user_id != $user->id) {
            return response()->json([
                'status' => false,
                'error' => 'Serviço não encontrado ou você não tem permissão para excluí-lo.'
            ], 403);
        }

        $servico->delete();

        return response()->json([
            'status' => true,
            'message' => 'Serviço excluído com sucesso.',
        ], 200);
    }

    public function getAvaliacoesServico($servicoId)
    {
        $servico = Servico::find($servicoId);

        if (!$servico) {
            return response()->json([
                'message' => 'Serviço não encontrado.'
            ], 404);
        }

        $avaliacoes = $servico->avaliacoes;

        if ($avaliacoes->isEmpty()) {
            return response()->json([
                'message' => 'Este serviço ainda não possui avaliações.'
            ]);
        }

        return response()->json($avaliacoes);
    }

    public function getServicos($agendadoId)
    {
        try {
            $agendado = Agendado::findOrFail($agendadoId); // Certifique-se de que o Agendado existe
            $servicos = $agendado->servicos; // Aqui ele pega os serviços relacionados ao agendado

            return response()->json(['servicos' => $servicos]);
        } catch (\Exception $e) {
            // Registra o erro nos logs e retorna a resposta apropriada
            Log::error('Erro ao buscar serviços: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao buscar serviços'], 500);
        }
    }


}
