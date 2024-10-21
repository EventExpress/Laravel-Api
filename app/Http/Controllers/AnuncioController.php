<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Categoria;
use App\Models\Endereco;
use App\Models\ImagemAnuncio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Adicione esta linha

class AnuncioController extends Controller
{
    // ... (outras funções)

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
                'imagens.*' => 'string', // para aceitar string Base64
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
                $imagemAnuncio->image_path = $imagemBase64; // armazena a string Base64 diretamente
                $imagemAnuncio->is_main = false; // define se é a imagem principal
                $imagemAnuncio->save();
            }

            $anuncio->categorias()->attach($validatedData['categoriaId']);

            DB::commit();

            // Log de sucesso na criação do anúncio
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

        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao tentar criar o anúncio
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

    // ... (outras funções)

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

            // Log de sucesso na atualização do anúncio
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

        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao tentar atualizar o anúncio
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

        DB::beginTransaction();
        try {
            $anuncio->delete();
            $anuncio->endereco()->delete();

            DB::commit();

            // Log de sucesso na exclusão do anúncio
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

            // Log de erro ao tentar excluir o anúncio
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
