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
use Illuminate\Validation\Rule;

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
            'tipousu' => 'required|array',
            'tipousu.*' => 'string|in:Locatario,Locador,Prestador,admin',
            'cpf' => 'required|integer|min:11|unique:users,cpf',
            'cnpj' => $request->input('tipousu') && in_array('Locador', $request->input('tipousu')) ? 'required|string|min:14|max:14|unique:users,cnpj' : 'nullable',
            'cidade' => 'required|string|min:3|max:255',
            'cep' => 'required|string|min:8|max:9',
            'numero' => 'required|integer|min:1',
            'bairro' => 'required|string|min:3|max:255'
        ]);

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
            $usuario->cpf = $request->cpf;
            $usuario->cnpj = $request->cnpj;
            $usuario->endereco_id = $endereco->id;
            $usuario->password = Hash::make($request['password']);
            $usuario->save();

            // Associar todos os tipos de usuário fornecidos
            $tipos = $request->input('tipousu');
            $typeUsers = TypeUser::whereIn('tipousu', $tipos)->pluck('id');
            $usuario->typeUsers()->sync($typeUsers);

            DB::commit();

            $token = $usuario->createToken('Personal Access Token after register')->plainTextToken;

            return response()->json([
                'message' => 'Usuário criado com sucesso!',
                'user' => $usuario,
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
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

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'nome' => 'sometimes|required|string|min:4|max:255',
            'sobrenome' => 'sometimes|required|string|min:4|max:255',
            'telefone' => 'sometimes|required|string|min:10|max:15',
            'datanasc' => 'sometimes|required|date',
            'email' => [
                'sometimes',
                'required',
                'email',
                'min:5',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'sometimes|required|string|min:8|max:255',
            'tipousu' => 'required|array',
            'tipousu.*' => 'string|in:Locatario,Locador,Prestador,admin',
            'cpf' => [
                'sometimes',
                'required',
                'integer',
                'digits:11',
                Rule::unique('users')->ignore($user->id),
            ],
            'cnpj' => $request->tipousu === 'Locador' ? 'sometimes|required|string|size:14|unique:users,cnpj,' . $user->id : 'nullable',
            'cidade' => 'sometimes|required|string|min:3|max:255',
            'cep' => 'sometimes|required|string|min:8|max:9',
            'numero' => 'sometimes|required|integer|min:1',
            'bairro' => 'sometimes|required|string|min:3|max:255',
        ];

        $validatedData = $request->validate($rules);

        DB::beginTransaction();

        try {
            // Remover tipousu do array de dados validados para evitar erro de coluna inexistente
            unset($validatedData['tipousu']);

            $user->update($validatedData);

            if ($user->nome) {
                $user->nome->update($request->only(['nome', 'sobrenome']));
            }

            if ($user->endereco) {
                $user->endereco->update($request->only(['cidade', 'cep', 'numero', 'bairro']));
            }

            if ($request->has('tipousu')) {
                $tipos = $request->input('tipousu');
                $typeUsers = TypeUser::whereIn('tipousu', $tipos)->pluck('id');
                $user->typeUsers()->sync($typeUsers);
            }

            DB::commit();

            return response()->json(['message' => 'Usuário atualizado com sucesso!', 'user' => $user], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Erro ao atualizar usuário.', 'error' => $e->getMessage()], 500);
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
