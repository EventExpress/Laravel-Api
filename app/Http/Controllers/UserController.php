<?php

namespace App\Http\Controllers;

use App\Models\TypeUser;
use App\Models\User;
use App\Models\Endereco;
use App\Models\Nome;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() : JsonResponse
    {
        //Busca os usuarios do banco de dados, ordenados pelo id em ordem decressente,paginados
        $users = User::orderby('id')->paginate(2);

        //Retorna os usuarios em json
        return response()->json([
            'status'=> true,
            'users' => $users,
        ],200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nome' => 'required|string|min:4|max:255',
            'sobrenome' => 'required|string|min:4|max:255',
            'telefone' => 'required|string|min:10|max:15',
            'datanasc' => 'required|date',
            'email' => 'required|email|min:5|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:255',
            'tipousu' => 'required|string|in:Cliente,Locador,Prestador',
            'cpf' => 'required|integer|min:11|unique:users,cpf',
            'cnpj' => $request->tipousu === 'Locador' ? 'required|string|min:14|max:14|unique:users,cnpj' : 'nullable',
            'cidade' => 'required|string|min:3|max:255',
            'cep' => 'required|string|min:8|max:9',
            'numero' => 'required|integer|min:1',
            'bairro' => 'required|string|min:3|max:255'
        ]);


         //desta forma caso tenha algum erro na criação de alguma tabela ele nao salva nenhum dos dados.
        DB::beginTransaction();

        try {
            $nome = new Nome();
            $nome->nome = $request->nome;
            $nome->sobrenome = $request->sobrenome;
            $nome->save();

            $endereco = new Endereco();
            $endereco->cidade = $request->cidade;
            $endereco->cep = $request->cep;
            $endereco->numero = $request->numero;
            $endereco->bairro = $request->bairro;
            $endereco->save();

            $usuario = new User();
            $usuario->nome_id = $nome->id;
            $usuario->telefone = $request->telefone;
            $usuario->datanasc = $request->datanasc;
            $usuario->email = $request->email;
            $usuario->tipousu = $request->tipousu;
            $usuario->cpf = $request->cpf;
            $usuario->cnpj = $request->cnpj;
            $usuario->endereco_id = $endereco->id;
            $usuario->password = Hash::make($request['password']);
            $usuario->save();

            $tipoUsuario = TypeUser::where('tipousu', $request->tipousu)->first();
            if ($tipoUsuario) {
                $usuario->typeUsers()->attach($tipoUsuario->id);
            }

            DB::commit();


            //apos criar o usuario é possivel logar diretamente no sistema...
            $token = $usuario->createToken('Personal Access Token after register')->plainTextToken;

            return response()->json([
                'message' => 'Usuário criado com sucesso!',
                'user' => $usuario,
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            // Reverte todas as operações se ocorrer algum erro
            DB::rollback();

            return response()->json([
                'message' => 'Erro ao criar usuário',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(User $user) : JsonResponse
    {
        return response()->json([
            'status' => true,
            'user' => $user,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user) : JsonResponse
    {
        // Validação dos dados com todos os campos como obrigatórios
        $request->validate([
            'nome' => 'sometimes|required|string|min:4|max:255',
            'sobrenome' => 'sometimes|required|string|min:4|max:255',
            'telefone' => 'sometimes|required|string|min:10|max:15',
            'datanasc' => 'sometimes|required|date',
            'email' => 'sometimes|required|email|min:5|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|max:255',
            'tipousu' => 'sometimes|required|string|in:Cliente,Locador,Prestador',
            'cpf' => 'sometimes|required|integer|min:11|unique:users,cpf,' . $user->id,
            'cnpj' => $request->input('tipousu') === 'Locador' ? 'sometimes|required|string|min:14|max:14|unique:users,cnpj,' . $user->id : 'nullable',
            'cidade' => 'sometimes|required|string|min:3|max:255',
            'cep' => 'sometimes|required|string|min:8|max:9',
            'numero' => 'sometimes|required|integer|min:1',
            'bairro' => 'sometimes|required|string|min:3|max:255'
        ]);

        DB::beginTransaction();

        try {
            // Atualiza ou cria o nome
            if ($request->has('nome') || $request->has('sobrenome')) {
                $nome = $user->nome ?? new Nome();
                $nome->nome = $request->input('nome', $nome->nome);
                $nome->sobrenome = $request->input('sobrenome', $nome->sobrenome);
                $nome->save();
                $user->nome_id = $nome->id;
            }

            // Atualiza ou cria o endereço
            if ($request->has('cidade') || $request->has('cep') || $request->has('numero') || $request->has('bairro')) {
                $endereco = $user->endereco ?? new Endereco();
                $endereco->cidade = $request->input('cidade', $endereco->cidade);
                $endereco->cep = $request->input('cep', $endereco->cep);
                $endereco->numero = $request->input('numero', $endereco->numero);
                $endereco->bairro = $request->input('bairro', $endereco->bairro);
                $endereco->save();
                $user->endereco_id = $endereco->id;
            }

            // Atualiza o usuário com os dados fornecidos
            $user->telefone = $request->input('telefone', $user->telefone);
            $user->datanasc = $request->input('datanasc', $user->datanasc);
            $user->email = $request->input('email', $user->email);
            $user->tipousu = $request->input('tipousu', $user->tipousu);
            $user->cpf = $request->input('cpf', $user->cpf);
            $user->cnpj = $request->input('cnpj', $user->cnpj);

            // Atualiza a senha se fornecida
            if ($request->filled('password')) {
                $user->password = Hash::make($request->input('password'));
            }

            $user->save();

            // Atualiza os tipos de usuário se fornecido
            if ($request->has('tipousu')) {
                $tipoUsuario = TypeUser::where('tipousu', $request->tipousu)->first();
                if ($tipoUsuario) {
                    $user->typeUsers()->sync([$tipoUsuario->id]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Usuário atualizado com sucesso!',
                'user' => $user,
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Erro ao atualizar usuário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user) : JsonResponse
    {
        DB::beginTransaction();

        try {
            $user->typeUsers()->detach();

            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Usuário deletado com sucesso!',
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Erro ao deletar usuário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
