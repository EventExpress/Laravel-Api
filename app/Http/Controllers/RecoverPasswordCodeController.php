<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordCodeRequest;
use App\Http\Requests\ResetPasswordValidateCodeRequest;
use App\Mail\SendEmailForgotPasswordCode;
use App\Models\User;
use App\Service\ResetPasswordValidateCodeService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RecoverPasswordCodeController extends Controller
{
    public function forgotPasswordCode(ForgotPasswordRequest $request): JsonResponse
    {

        // Recuperar os dados do usuário no banco de dados com o e-mail
        $user = User::where('email', $request->email)->first();

        if (!$user) {

            Log::warning('Tentativa recuperar senha com e-mail não cadastrado.', ['email' => $request->email]);

            return response()->json([
                'status' => false,
                'message' => 'E-mail não encontrado!',
            ], 400);
        }

        try {

            // Recuperar os registros recuperar senha do usuário
            $userPasswordResets = DB::table('password_reset_tokens')->where([
                ['email', $request->email]
            ]);

            // Se existir token cadastrado para o usuário recuperar senha, excluir o mesmo
            if ($userPasswordResets) {
                $userPasswordResets->delete();
            }

            // Gerar o código com 6 digitos
            $code = mt_rand(100000, 999999);

            // Criptografar o código
            $token = Hash::make($code);

            // Salvar o token no banco de dados
            $userNewPasswordResets = DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now(),
            ]);


            // Enviar e-mail após cadastrar no banco de dados novo token recuperar senha
            if ($userNewPasswordResets) {

                // Obter a data atual
                $currentDate = Carbon::now();

                $oneHourLater = $currentDate->addHour();

                // Formatar data e hora
                $formattedTime = $oneHourLater->format('H:i');
                $formattedDate = $oneHourLater->format('d/m/Y');

                // Dados para enviar e-mail
                Mail::to($user->email)->send(new SendEmailForgotPasswordCode($user, $code, $formattedDate, $formattedTime));
            }

            Log::info('Recuperar senha.', ['email' => $request->email]);


            return response()->json([
                'status' => true,
                'message' => 'Enviado e-mail com instruções para recuperar a senha. Acesse a sua caixa de e-mail para recuperar a senha!',
            ], 200);
        } catch (Exception $e) {

            Log::warning('Erro recuperar senha.', ['email' => $request->email, 'error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Erro recuperar senha. Tente mais tarde!',
            ], 400);
        }
    }

    public function resetPasswordValidateCode(ResetPasswordValidateCodeRequest $request, ResetPasswordValidateCodeService $resetPasswordValidateCode): JsonResponse
    {

        try{

            // Validar o código do token
            $validationResult = $resetPasswordValidateCode->resetPasswordValidateCode($request->email, $request->code);

            if(!$validationResult['status']){

                return response()->json([
                    'status' => false,
                    'message' => $validationResult['message'],
                ], 400);

            }

            // Recuperar os dados do usuário
            $user = User::where('email', $request->email)->first();

            if(!$user){

                Log::notice('Usuário não encontrado.', ['email' => $request->email]);

                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não encontrado!',
                ], 400);

            }

            Log::info('Código recuperar senha válido.', ['email' => $request->email]);

            return response()->json([
                'status' => true,
                'message' => 'Código recuperar senha válido!',
            ], 200);

        } catch (Exception $e){

            Log::warning('Erro validar código recuperar senha.', ['email' => $request->email, 'error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Código inválido!',
            ], 400);
        }
    }
    public function resetPasswordCode(ResetPasswordCodeRequest $request, ResetPasswordValidateCodeService $resetPasswordValidateCode): JsonResponse
    {

        try{

            // Validar o código do token
            $validationResult = $resetPasswordValidateCode->resetPasswordValidateCode($request->email, $request->code);

            if(!$validationResult['status']){

                return response()->json([
                    'status' => false,
                    'message' => $validationResult['message'],
                ], 400);

            }

            $user = User::where('email', $request->email)->first();

            // Verificar existe o usuário no banco de dados
            if(!$user){
                Log::notice('Usuário não encontrado.', ['email' => $request->email]);

                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não encontrado!',
                ], 400);

            }

            // Alterar a senha do usuário no banco de dados
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // gerar o token
            $token = $user->first()->createToken('api-token')->plainTextToken;

            $userPasswordResets = DB::table('password_reset_tokens')->where('email', $request->email);

            if($userPasswordResets){
                $userPasswordResets->delete();
            }

            Log::info('Senha atualizada com sucesso.', ['email' => $request->email]);

            return response()->json([
                'status' => true,
                'user' => $user,
                'token' => $token,
                'message' => 'Senha atualizada com sucesso!',
            ], 200);
        }catch(Exception $e){

            Log::warning('Senha não atualizada.', ['email' => $request->email, 'error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Senha não atualizada!',
            ], 400);

        }

    }




}
