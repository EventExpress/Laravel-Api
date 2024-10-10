<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Categoria;
use App\Models\Endereco;
use App\Models\ImagemAnuncio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnuncioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $anuncios = Anuncio::all();

        return response()->json([
            'status' => true,
            'anuncios' => $anuncios,
        ], 200);
    }

    public function indexNoAuth()
    {
        $anuncios = Anuncio::all();
        return response()->json([
            'status' => true,
            'anuncios' => $anuncios,
        ], 200);
    }

    public function meusAnuncios()
    {
        $user = Auth::user();

        if ($user->typeUsers->first()->tipousu !== 'locador') {
            return response()->json([
                'status' => false,
                'error' => 'Você não tem permissão para criar anúncios.'
            ], 403);
        }

        $user_id = $user->id;
        $anuncios = Anuncio::where('user_id', $user_id)->get();

        return response()->json([
            'status' => true,
            'anuncios' => $anuncios,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();

        if ($user->typeUsers->first()->tipousu !== 'locador') {
            return response()->json([
                'status' => false,
                'error' => 'Você não tem permissão para criar anúncios.'
            ], 403);
        }

        $categorias = Categoria::all();

        return response()->json([
            'status' => true,
            'user' => $user,
            'categorias' => $categorias,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validate([
                'titulo' => 'required|string|min:4|max:255',
                'cidade' => 'required|string|min:3|max:255',
                'cep' => 'required|string|min:8|max:9',
                'numero' => 'required|integer|min:1',
                'bairro' => 'required|string|min:3|max:255',
                'capacidade' => 'required|integer|min:1|max:10000',
                'descricao' => 'required|string|min:10|max:2000',
                'valor' => 'required|numeric|min:0',
                'agenda' => 'required|date',
                'categoriaId' => 'required|array',
                'imagens' => 'required|array',
                'imagens.*' => 'image|mimes:jpg,jpeg,png|max:2048', // Validação para cada imagem
            ]);

            $endereco = new Endereco();
            $endereco->cidade = $validatedData['cidade'];
            $endereco->cep = $validatedData['cep'];
            $endereco->numero = $validatedData['numero'];
            $endereco->bairro = $validatedData['bairro'];
            $endereco->save();

            $anuncio = new Anuncio();
            $anuncio->user_id = Auth::id();
            $anuncio->endereco_id = $endereco->id;
            $anuncio->titulo = $validatedData['titulo'];
            $anuncio->capacidade = $validatedData['capacidade'];
            $anuncio->descricao = $validatedData['descricao'];
            $anuncio->valor = $validatedData['valor'];
            $anuncio->agenda = $validatedData['agenda'];
            $anuncio->save();

            foreach ($validatedData['imagens'] as $imagem) {
                // Armazena a imagem e obtém o caminho
                $imagePath = $imagem->store('imagens/anuncios');

                $imagemAnuncio = new ImagemAnuncio();
                $imagemAnuncio->anuncio_id = $anuncio->id;
                $imagemAnuncio->image_path = $imagePath;
                $imagemAnuncio->is_main = false;
                $imagemAnuncio->save();
            }

            // Anexar categorias ao anúncio
            $anuncio->categorias()->attach($validatedData['categoriaId']);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Anúncio criado com sucesso.',
                'anuncio' => $anuncio,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback em caso de erro
            return response()->json([
                'status' => false,
                'message' => 'Erro ao criar anúncio: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $search = $request->input('search');

        $results = Anuncio::whereHas('endereco', function ($query) use ($search) {
            $query->where('cidade', 'like', "%$search%")
                ->orWhere('cep', 'like', "%$search%")
                ->orWhere('numero', 'like', "%$search%")
                ->orWhere('bairro', 'like', "%$search%");
        })
            ->orWhere('titulo', 'like', "%$search%")
            ->orWhere('capacidade', 'like', "%$search%")
            ->orWhere('descricao', 'like', "%$search%")
            ->orWhereHas('user', function ($query) use ($search) {
                $query->where('nome', 'like', "%$search%");
            })
            ->orWhere('valor', 'like', "%$search%")
            ->orWhere('agenda', 'like', "%$search%")
            ->get();

        return response()->json([
            'status' => true,
            'results' => $results,
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $anuncio = Anuncio::find($id);

        $user = Auth::user();

        if (!$anuncio || $anuncio->user_id != $user->id) {
            return response()->json([
                'status' => false,
                'error' => 'Anúncio não encontrado ou você não tem permissão para editá-lo.'
            ], 403);
        }

        $categorias = Categoria::all();
        $categoriaSelecionada = $anuncio->categorias->pluck('id')->toArray();

        return response()->json([
            'status' => true,
            'anuncio' => $anuncio,
            'categorias' => $categorias,
            'categoriaSelecionada' => $categoriaSelecionada,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validate([
                'titulo' => 'required|string|min:4|max:255',
                'cidade' => 'required|string|min:3|max:255',
                'cep' => 'required|string|min:8|max:9',
                'numero' => 'required|integer|min:1',
                'bairro' => 'required|string|min:3|max:255',
                'capacidade' => 'required|integer|min:1|max:10000',
                'descricao' => 'required|string|min:10|max:2000',
                'categoriaId' => 'required|array',
                'imagens' => 'nullable|array',
                'imagens.*' => 'image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $user = Auth::user();
            $anuncio = Anuncio::find($id);

            if (!$anuncio || $anuncio->user_id != $user->id) {
                return response()->json([
                    'status' => false,
                    'error' => 'Anúncio não encontrado ou você não tem permissão para editá-lo.'
                ], 403);
            }

            $anuncio->update([
                'titulo' => $validatedData['titulo'],
                'capacidade' => $validatedData['capacidade'],
                'descricao' => $validatedData['descricao'],
            ]);

            $endereco = Endereco::find($anuncio->endereco_id);
            $endereco->update([
                'cidade' => $validatedData['cidade'],
                'cep' => $validatedData['cep'],
                'numero' => $validatedData['numero'],
                'bairro' => $validatedData['bairro'],
            ]);


            if (isset($validatedData['imagens'])) {
                foreach ($validatedData['imagens'] as $imagem) {
                    // Armazena a nova imagem e obtém o caminho
                    $imagePath = $imagem->store('imagens/anuncios');

                    $imagemAnuncio = new ImagemAnuncio();
                    $imagemAnuncio->anuncio_id = $anuncio->id;
                    $imagemAnuncio->image_path = $imagePath;
                    $imagemAnuncio->is_main = false;
                    $imagemAnuncio->save();
                }
            }


            $anuncio->categorias()->sync($validatedData['categoriaId']);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Anúncio atualizado com sucesso.',
                'anuncio' => $anuncio,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Erro ao atualizar anúncio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $anuncio = Anuncio::find($id);

        if (!$anuncio || $anuncio->user_id != $user->id) {
            return response()->json([
                'status' => false,
                'error' => 'Anúncio não encontrado ou você não tem permissão para excluí-lo.'
            ], 403);
        }

        $anuncio->delete();
        $anuncio->endereco()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Anúncio excluído com sucesso.'
        ], 200);
    }
}
