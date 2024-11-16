<?php
namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\Endereco;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() : JsonResponse
    {
        $users = User::orderby('id')->paginate(2);

        Log::channel('main')->info('User list retrieved', [
            'total_users' => count($users),
            'fetched_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'users' => $users,
        ], 200);
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
            'cpf' => 'required|string|min:11|unique:users,cpf',
            'cnpj' => $request->input('tipousu') && in_array('Locador', $request->input('tipousu')) ? 'required|string|min:14|max:18|unique:users,cnpj' : 'nullable',
            'cidade' => 'required|string|min:3|max:255',
            'cep' => 'required|string|min:8|max:9',
            'numero' => 'required|integer|min:1',
            'bairro' => 'required|string|min:3|max:255'
        ]);

        DB::beginTransaction();

        try {
            $endereco = new Endereco();
            $endereco->cidade = $request->cidade;
            $endereco->cep = $request->cep;
            $endereco->numero = $request->numero;
            $endereco->bairro = $request->bairro;
            $endereco->save();

            $usuario = new User();
            $usuario->nome = $request->nome;
            $usuario->sobrenome = $request->sobrenome;
            $usuario->telefone = $request->telefone;
            $usuario->datanasc = $request->datanasc;
            $usuario->email = $request->email;
            $usuario->cpf = $request->cpf;
            $usuario->cnpj = $request->cnpj;
            $usuario->endereco_id = $endereco->id;
            $usuario->password = Hash::make($request['password']);
            $usuario->save();

            $tipos = $request->input('tipousu');
            $typeUsers = TypeUser::whereIn('tipousu', $tipos)->pluck('id');
            $usuario->typeUsers()->sync($typeUsers);

            DB::commit();

            $token = $usuario->createToken('Personal Access Token after register')->plainTextToken;

            Log::channel('main')->info('User created', [
                'user_id' => $usuario->id,
                'user_name' => $usuario->nome . ' ' . $usuario->sobrenome,
                'user_email' => $usuario->email,
                'user_type' => $tipos,
                'address' => [
                    'city' => $endereco->cidade,
                    'cep' => $endereco->cep,
                    'number' => $endereco->numero,
                    'neighborhood' => $endereco->bairro,
                ],
                'created_at' => now(),
            ]);

            return response()->json([
                'message' => 'Usuário criado com sucesso!',
                'user' => $usuario,
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            Log::channel('main')->error('User creation failed', [
                'error_message' => $e->getMessage(),
                'occurred_at' => now(),
            ]);

            return response()->json([
                'message' => 'Erro ao criar usuário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id) : JsonResponse
    {
        try {
            $user = User::with(['endereco', 'typeUsers'])->findOrFail($id);

            Log::channel('main')->info('User retrieved', [
                'user_id' => $user->id,
                'user_name' => $user->nome . ' ' . $user->sobrenome,
                'user_email' => $user->email,
                'fetched_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            Log::channel('main')->error('User retrieval failed', [
                'user_id' => $id,
                'error_message' => $e->getMessage(),
                'occurred_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Usuário não encontrado',
                'error' => $e->getMessage(),
            ], 404);
        }
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
            'tipousu' => 'sometimes|required|array', // Tipousu é opcional, mas se fornecido, deve ser um array
            'tipousu.*' => 'string|in:Locatario,Locador,Prestador,admin',
            'cpf' => [
                'sometimes',
                'required',
                'string',
                'digits:11',
                Rule::unique('users')->ignore($user->id),
            ],
            'cnpj' => $request->tipousu === 'Locador' ? 'sometimes|required|string|size:18|unique:users,cnpj,' . $user->id : 'nullable',
            'cidade' => 'sometimes|required|string|min:3|max:255',
            'cep' => 'sometimes|required|string|min:8|max:9',
            'numero' => 'sometimes|required|integer|min:1',
            'bairro' => 'sometimes|required|string|min:3|max:255',
        ];

        $validatedData = $request->validate($rules);

        DB::beginTransaction();

        try {
            unset($validatedData['tipousu']);

            $user->update($validatedData);

            if ($user->endereco) {
                $user->endereco->update($request->only(['cidade', 'cep', 'numero', 'bairro']));
            }

            if ($request->has('tipousu')) {
                $tipos = $request->input('tipousu');
                $typeUsers = TypeUser::whereIn('tipousu', $tipos)->pluck('id');
                $user->typeUsers()->sync($typeUsers);
            }

            DB::commit();

            Log::channel('main')->info('User updated', [
                'user_id' => $user->id,
                'updated_at' => now(),
                'updated_fields' => $validatedData,
                'updated_by_user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Usuário atualizado com sucesso!',
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('main')->error('User update failed', [
                'user_id' => $id,
                'error_message' => $e->getMessage(),
                'occurred_at' => now(),
            ]);

            return response()->json([
                'message' => 'Erro ao atualizar usuário.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) : JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);

            $user->typeUsers()->detach();

            $user->delete();

            DB::commit();

            $deletedBy = Auth::user();

            Log::channel('main')->info('User deleted', [
                'user_id' => $id,
                'deleted_by_user_id' => $deletedBy->id,
                'deleted_by_name' => $deletedBy->nome,
                'deleted_at' => now(),
            ]);

            return response()->json([
                'message' => 'Usuário deletado com sucesso!',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('main')->error('User deletion failed', [
                'user_id' => $id,
                'error_message' => $e->getMessage(),
                'occurred_at' => now(),
            ]);

            return response()->json([
                'message' => 'Erro ao deletar usuário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAvaliacoesUsuario($userId)
    {
        $user = User::findOrFail($userId);

        $avaliacoes = $user->avaliacoes;

        return response()->json($avaliacoes);
    }

}

