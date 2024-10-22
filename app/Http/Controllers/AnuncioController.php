<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Categoria;
use App\Models\Endereco;
use App\Models\ImagemAnuncio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class AnuncioController extends Controller
{
    public function index()
    {
        $anuncios = Anuncio::with('imagens')->get();

        return response()->json([
            'status' => true,
            'anuncios' => $anuncios,
        ]);
    }

    public function apresentaCategoriaAnuncio()
    {
        $categoria = Categoria::all();
        return response()->json(['categorias' => $categoria], 200);
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
    }

    public function indexNoAuth()
    {
        $anuncios = Anuncio::with('imagens')->get();
        return response()->json([
            'status' => true,
            'anuncios' => $anuncios,
        ], 200);
    }

    public function create()
    {
        $user = Auth::user();

        if ($user->typeUsers->first()->tipousu !== 'locador') {
            return response()->json([
                'status' => false,
                'error' => 'Você não tem permissão para criar anúncios.'
            ], 403);
        }
    }

    public function store(Request $request)
    {
        //
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
                'imagens.*' => 'string',
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

            foreach ($validatedData['imagens'] as $imagemBase64) {
                $imagemAnuncio = new ImagemAnuncio();
                $imagemAnuncio->anuncio_id = $anuncio->id;
                $imagemAnuncio->image_path = $imagemBase64;
                $imagemAnuncio->is_main = false;
                $imagemAnuncio->save();
            }

            $anuncio->categorias()->attach($validatedData['categoriaId']);

            DB::commit();

            Log::channel('loganuncios')->info('Anuncio created', [
                'anuncio_id' => $anuncio->id,
                'user_id' => Auth::id(),
                'created_at' => now(),
                'titulo' => $anuncio->titulo,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Anúncio criado com sucesso.',
                'anuncio' => $anuncio,
            ], 201);


        }catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('loganuncios')->error('Failed to create Anuncio', [
                'error_message' => $e->getMessage(),
                'request_data' => $request->all(),
                'occurred_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erro ao criar anúncio: ' . $e->getMessage()
            ], 500);
        }
    }

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

            Log::channel('loganuncios')->info('Anuncio updated', [
                'anuncio_id' => $anuncio->id,
                'user_id' => Auth::id(),
                'updated_at' => now(),
                'titulo' => $anuncio->titulo,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Anúncio atualizado com sucesso.',
                'anuncio' => $anuncio,
            ], 200);

        }catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('loganuncios')->error('Failed to update Anuncio', [
                'anuncio_id' => $id,
                'error_message' => $e->getMessage(),
                'request_data' => $request->all(),
                'occurred_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erro ao atualizar anúncio: ' . $e->getMessage()
            ], 500);
        }
    }

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

        DB::beginTransaction();
        try {
            $anuncio->delete();
            $anuncio->endereco()->delete();

            DB::commit();

            Log::channel('loganuncios')->info('Anuncio deleted', [
                'anuncio_id' => $anuncio->id,
                'user_id' => Auth::id(),
                'deleted_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Anúncio excluído com sucesso.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('loganuncios')->error('Failed to delete Anuncio', [
                'anuncio_id' => $id,
                'error_message' => $e->getMessage(),
                'occurred_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erro ao excluir anúncio: ' . $e->getMessage()
            ], 500);
        }
    }
}
