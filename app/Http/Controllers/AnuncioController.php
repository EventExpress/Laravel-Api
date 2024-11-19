<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Categoria;
use App\Models\Endereco;
use App\Models\ImagemAnuncio;
use App\Models\Scategoria;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnuncioController extends Controller
{
    public function index()
    {
        $anuncios = Anuncio::with('imagens', 'endereco')->get();

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

        if ($user->typeUsers->first()->tipousu !== 'Locador') {
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
                'agenda' => 'nullable|array',
                'agenda.*' => 'date',
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
            $anuncio->status = 'ativo';

            if (!empty($validatedData['agenda'])) {
                $anuncio->agenda = json_encode($validatedData['agenda']);
            } else {
                $anuncio->agenda = json_encode([]); // inicializa como um array vazio se não houver datas
            }

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

        if (empty($search)) {
            return response()->json([
                'status' => false,
                'message' => 'Por favor, insira um termo de pesquisa.',
            ], 400);
        }

        $results = Anuncio::with('imagens')->
            whereHas('endereco', function ($query) use ($search) {
            $query->where('cidade', 'like', "%$search%")
                ->orWhere('bairro', 'like', "%$search%");
        })
            ->orWhere('titulo', 'like', "%$search%")
            ->orWhere('capacidade', 'like', "%$search%")
            ->orWhere('descricao', 'like', "%$search%")
            ->orWhereHas('user', function ($query) use ($search) {
                $query->where('nome', 'like', "%$search%");
            })
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
                'titulo' => 'sometimes|required|string|min:4|max:255',
                'cidade' => 'sometimes|required|string|min:3|max:255',
                'cep' => 'sometimes|required|string|min:8|max:9',
                'numero' => 'sometimes|required|integer|min:1',
                'bairro' => 'sometimes|required|string|min:3|max:255',
                'capacidade' => 'sometimes|required|integer|min:1|max:10000',
                'descricao' => 'sometimes|required|string|min:10|max:2000',
                'categoriaId' => 'sometimes|required|array',
                'imagens' => 'nullable|array',
                'imagens.*' => 'image|mimes:jpg,jpeg,png|max:2048',
                'status' => 'sometimes|required|in:ativo,inativo',
                'agenda' => 'nullable|array',
            ]);

            $user = Auth::user();
            $anuncio = Anuncio::find($id);

            // Verifica se o anúncio existe e se o usuário é o criador
            if (!$anuncio || $anuncio->user_id != $user->id) {
                return response()->json([
                    'status' => false,
                    'error' => 'Anúncio não encontrado ou você não tem permissão para editá-lo.'
                ], 403);
            }

            $anuncio->update(array_filter([
                'titulo' => $validatedData['titulo'] ?? $anuncio->titulo,
                'capacidade' => $validatedData['capacidade'] ?? $anuncio->capacidade,
                'descricao' => $validatedData['descricao'] ?? $anuncio->descricao,
                'status' => $validatedData['status'] ?? $anuncio->status,
                'agenda' => $validatedData['agenda'] ?? $anuncio->agenda,
            ]));

            $endereco = Endereco::find($anuncio->endereco_id);
            $endereco->update(array_filter([
                'cidade' => $validatedData['cidade'] ?? $endereco->cidade,
                'cep' => $validatedData['cep'] ?? $endereco->cep,
                'numero' => $validatedData['numero'] ?? $endereco->numero,
                'bairro' => $validatedData['bairro'] ?? $endereco->bairro,
            ]));

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

            if (isset($validatedData['categoriaId'])) {
                $anuncio->categorias()->sync($validatedData['categoriaId']);
            }

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

        } catch (\Illuminate\Validation\ValidationException $e) {
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

    public function getUnavailableDates($id)
    {
        // Encontra o anúncio pelo ID
        $anuncio = Anuncio::find($id);

        // Verifica se o anúncio existe
        if (!$anuncio) {
            return response()->json([
                'status' => false,
                'message' => 'Anúncio não encontrado.'
            ], 404);
        }

        // Decodifica o campo 'agenda' do anúncio
        $unavailableDates = json_decode($anuncio->agenda, true);

        // Retorna as datas indisponíveis
        return response()->json([
            'status' => true,
            'unavailable_dates' => $unavailableDates,
        ], 200);
    }

    public function verificarDisponibilidade ($id)
    {
        // Encontra o anúncio pelo ID
        $anuncio = Anuncio::find($id);

        // Verifica se o anúncio existe
        if (!$anuncio) {
            return response()->json([
                'status' => false,
                'message' => 'Anúncio não encontrado.'
            ], 404);
        }

        // Decodifica o campo 'agenda' do anúncio
        $unavailableDates = json_decode($anuncio->agenda, true);

        // Retorna as datas indisponíveis
        return response()->json([
            'status' => true,
            'unavailable_dates' => $unavailableDates,
        ], 200);
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

    public function anunciosPorTituloCategoria($titulo)
    {
        // Verifica se a categoria existe pelo título
        $categoria = Categoria::where('titulo', $titulo)->first();
        if (!$categoria) {
            return response()->json([
                'status' => false,
                'message' => 'Categoria não encontrada.'
            ], 404);
        }

        // Busca anúncios que estão associados à categoria
        $anuncios = Anuncio::with('imagens')
            ->whereHas('categorias', function ($query) use ($categoria) {
                $query->where('id', $categoria->id);
            })
            ->get();

        return response()->json([
            'status' => true,
            'anuncios' => $anuncios,
        ], 200);
    }

    public function getServicosPorCidade($anuncio_id)
    {
        $anuncio = Anuncio::find($anuncio_id);

        $cidadeAnuncio = $anuncio->endereco->cidade;

        $servicos = Servico::where('cidade', $cidadeAnuncio)
            ->with('scategorias')
            ->get();

        return response()->json($servicos);
    }


    public function getAvaliacoesAnuncio($anuncioId)
    {
        $anuncio = Anuncio::findOrFail($anuncioId);

        $avaliacoes = $anuncio->avaliacoes;

        if ($avaliacoes->isEmpty()) {
            return response()->json([
                'message' => 'Este anúncio ainda não possui avaliações.'
            ]);
        }

        $mediaAvaliacoes = $avaliacoes->avg('nota');

        $avaliacoesComDetalhes = $avaliacoes->map(function ($avaliacao) {
            $usuario = User::find($avaliacao->user_id);

            return [
                'comentario' => $avaliacao->comentario,
                'usuario' => $usuario ? $usuario->nome : 'Usuário não encontrado',
                'usuario_id' => $avaliacao->user_id,
                'nota' => $avaliacao->nota,
                'data' => $avaliacao->created_at->format('d/m/Y'),
            ];
        });

        return response()->json([
            'media_avaliacoes' => $mediaAvaliacoes,
            'avaliacoes' => $avaliacoesComDetalhes
        ]);
    }

    public function apresentaCategoriaServico(): JsonResponse
    {
        $Scategorias = Scategoria::all();
        return response()->json(['Scategorias' => $Scategorias], 200);
    }
}
