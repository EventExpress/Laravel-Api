<?php

namespace App\Http\Controllers;

use App\Models\TypeUser;
use App\Models\User;
use App\Models\Endereco;
use App\Models\Nome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
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
    public function store(Request $request)
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
    public function show(User $user)
    {
        return response()->json([
            'status'=> true,
            'users' => $user,
        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
