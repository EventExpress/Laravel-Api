<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgendadoController;
use App\Http\Controllers\AnuncioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComprovanteController;
use App\Http\Controllers\Dashboards\DashboardAdminController;
use App\Http\Controllers\Dashboards\DashboardController;
use App\Http\Controllers\RecoverPasswordCodeController;
use App\Http\Controllers\ServicoController;
use App\Http\Controllers\UserController;
use App\Http\Middeleware\AdminAccess;
use Illuminate\Support\Facades\Route;

// Rotas públicas (registro e login)
Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/anuncios/noauth', [AnuncioController::class, 'indexNoAuth']);

Route::post("/forgot-password-code", [RecoverPasswordCodeController::class, 'forgotPasswordCode']);
Route::post("/reset-password-validate-code", [RecoverPasswordCodeController::class, 'resetPasswordValidateCode']);
Route::post("/reset-password-code", [RecoverPasswordCodeController::class, 'resetPasswordCode']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
// Rotas para usuário
    Route::post('/user/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', [AuthController::class, 'profile']);
    Route::get('/user/{id}', [UserController::class, 'show']);
    Route::put('/user/{id}', [UserController::class, 'update']);
    Route::delete('/user/{id}', [UserController::class, 'destroy']); // Soft delete

// Rotas para anúncios
    Route::get('/anuncios', [AnuncioController::class, 'index']);
    Route::post('/anuncios', [AnuncioController::class, 'store']);
    Route::get('/anuncios/meus', [AnuncioController::class, 'meusAnuncios']);
    Route::get('/anuncios/{id}', [AnuncioController::class, 'show']);
    Route::put('/anuncios/{id}', [AnuncioController::class, 'update']);
    Route::delete('/anuncios/{id}', [AnuncioController::class, 'destroy']);
    Route::get('/anuncios/categoria/titulo/{titulo}', [AnuncioController::class, 'anunciosPorTituloCategoria']);



    Route::get('/categoria', [AnuncioController::class, 'apresentaCategoriaAnuncio']);

// Rota de busca por anúncios sem autenticação
    Route::get('/anuncios/buscar', [AnuncioController::class, 'indexNoAuth']);

//Rotas para serviços
    Route::get('/servicos', [ServicoController::class, 'index']);
    Route::get('/servicos/meus', [ServicoController::class, 'meusServicos']);
    Route::get('/servicos/create', [ServicoController::class, 'create']);
    Route::post('/servicos', [ServicoController::class, 'store']);
    Route::get('/servicos/{id}', [ServicoController::class, 'show']);
    Route::put('/servicos/{id}', [ServicoController::class, 'update']);
    Route::delete('/servicos/{id}', [ServicoController::class, 'destroy']);

    Route::get('/agendados', [AgendadoController::class, 'index']);
    Route::get('/agendados/meus', [AgendadoController::class, 'meusAgendados']);
    Route::post('/agendados/create', [AgendadoController::class, 'create']);
    Route::post('/agendados/{anuncio_id}', [AgendadoController::class, 'store']);
    Route::get('/agendados/{anuncio_id}', [AgendadoController::class, 'show']);
    Route::put('/agendados/{agendado_id}', [AgendadoController::class, 'update']);
    Route::delete('/agendados/{agendado_id}', [AgendadoController::class, 'destroy']);

    Route::post('/agendados/{id}/aprovar-pagamento', [AgendadoController::class, 'aprovarPagamento'])->name('agendados.aprovarPagamento');


    Route::get('/verifica-agenda/{id}', [AnuncioController::class, 'verificarDisponibilidade']);

    Route::get('/comprovantes/show', [ComprovanteController::class, 'show']);


//Rotas para preencher os relatórios
    Route::get('/dashboard/anuncios', [DashboardController::class, 'getAnuncios']);
    Route::get('/dashboard/agendados', [DashboardController::class, 'getAgendados']);
    Route::get('/dashboard/servicos', [DashboardController::class, 'relatorioCategoriasMaisReservadas']);
    Route::get('/dashboard/reservas-mensais', [DashboardController::class, 'relatorioReservasMensais']);
    Route::get('/dashboard/reservas-anuais', [DashboardController::class, 'relatorioReservasAnuais']);

});

// Rotas protegidas para administradores
Route::middleware(['auth:sanctum', 'AdminAccess'])->group(function () {
    Route::get('/admin', function () {
        return "Hello Admin";
    });

    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('api.admin.dashboard');

//Restaurar usuários ou anúncios excluídos (soft delete)
    Route::post('/admin/{id}/restore', [AdminController::class, 'restore']);

    Route::patch('/admin/user/restore/{id}', [AdminController::class, 'restoreUser']);

    Route::patch('/admin/anuncios/restore/{id}', [AdminController::class, 'restoreAnuncio']);

    Route::patch('/admin/servicos/restore/{id}', [AdminController::class, 'restoreServico']);

    Route::delete('/admin/user/{id}', [AdminController::class, 'destroyUser']);

    Route::delete('/admin/servicos/{id}', [AdminController::class, 'destroyServico']);

    Route::delete('/admin/anuncios/{id}', [AdminController::class, 'destroyAnuncio']);

//Dashboard Admin
    Route::get('/dashboard/locacoes', [DashboardAdminController::class, 'getLocacoesPorMes']);
    Route::get('/dashboard/lucros', [DashboardAdminController::class, 'getLucrosPorMes']);
    Route::get('/dashboard/usuarios-mensais', [DashboardAdminController::class, 'getUsuariosPorMes']);

});
