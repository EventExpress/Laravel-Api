<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Servico;
use App\Models\Categoria;
use Illuminate\Http\Request;

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
    
    public function meusServicos()
    {
        $user = Auth::user();
        if ($user->typeUsers->first()->tipousu !== 'prestador') {
            return response()->json([
                'status' => false,
                'error' => 'Você não tem permissão para criar serviços.'
            ], 403);
        }
    
        $user_id = $user->id;
        $servicos = Servico::where('usuario_id', $user_id)->get();
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

        $categorias = Categoria::all();
        return response()->json([
            'status' => true,
            'user' => $user,
            'categorias' => $categorias,
        ], 200);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'titulo' => 'required|string|min:4|max:255',
            'cidade' => 'required|string|min:3|max:255',
            'bairro' => 'required|string|min:3|max:255',
            'descricao' => 'required|string|min:10|max:2000',
            'valor' => 'required|numeric|min:0',
            'agenda' => 'required|date',
            'categoriaId' => 'required|array',
        ]);
        
        $servico = new Servico();
        $servico->user_id = Auth::id();
        $servico->titulo = $validatedData['titulo'];
        $servico->cidade = $validatedData['cidade'];
        $servico->bairro = $validatedData['bairro'];
        $servico->descricao = $validatedData['descricao'];
        $servico->valor = $validatedData['valor'];
        $anuncio->agenda = $validatedData['agenda'];
        $servico->save();

        if (!$servico) {
            return response()->json([
                'status' => false,
                'message' => 'Erro ao criar serviço'
            ], 500);
        }

        $servico->categorias()->attach($validatedData['categoriaId']);

        return response()->json([
            'status' => true,
            'message' => 'Serviço criado com sucesso.',
            'servico' => $servico,
        ], 201);
    }

    public function show(Request $request)
    {
        $search = $request->input('search');

            $servico = Servico::where('titulo','like',"%$search%") 
                ->orwhere('cidade', 'like', "%$search%")
                ->orWhere('bairro', 'like', "%$search%")
                ->orWhere('descricao', 'like', "%$search%")
                ->orWhere('valor','like',"%$search%")
                ->orWhere('agenda', 'like', "%$search%")
                ->orWhereHas('user', function ($query) use ($search) {
                    $query->where('nome', 'like', "%$search%");
                })
                ->get();

        return response()->json([
            'status' => true,
            'results' => $results,
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

        $categorias = Categoria::all();
        $categoriaSelecionada = $servico->categorias->pluck('id')->toArray();

        return response()->json([
            'status' => true,
            'servico' => $servico,
            'categorias' => $categorias,
            'categoriaSelecionada' => $categoriaSelecionada,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'titulo' => 'required|string|min:4|max:255',
            'cidade' => 'required|string|min:3|max:255',
            'bairro' => 'required|string|min:3|max:255',
            'descricao' => 'required|string|min:10|max:2000',
            'categoriaId' => 'required|array'
        ]);

        $user = Auth::user();
        $servico = Servico::find($id);

        if (!$servico || $servico->user_id != $user->id) {
            return response()->json([
                'status' => false,
                'error' => 'Serviço não encontrado ou você não tem permissão para editá-lo.'
            ], 403);
        }

        $servico->update([
            'titulo' => $validatedData['titulo'],
            'descricao' => $validatedData['descricao'],
            'cidade' => $validatedData['cidade'],
            'bairro' => $validatedData['bairro'],
        ]);

        $servico->categorias()->sync($validatedData['categoriaId']);

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
        $servico->servico()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Serviço excluído com sucesso.',
        ], 200);
    }
}
