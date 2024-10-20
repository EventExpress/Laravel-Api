<?php

use App\Http\Controllers\ServicoController;
use App\Http\Controllers\AgendadoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AnuncioController;
use Illuminate\Support\Facades\Route;

// Rotas públicas (registro e login)
Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/anuncios/noauth', [AnuncioController::class, 'indexNoAuth']);

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
    Route::get('/agendados/create', [AgendadoController::class, 'create']);
    Route::post('/agendados', [AgendadoController::class, 'store']);
    Route::get('/agendados/{id}', [AgendadoController::class, 'show']);
    Route::put('/agendados/{id}', [AgendadoController::class, 'update']);
    Route::delete('/agendados/{id}', [AgendadoController::class, 'destroy']);
});

// Rotas protegidas para administradores
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin', function () {
        return "Hello Admin";
    });

    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('api.admin.dashboard');

    // Restaurar usuários ou anúncios excluídos (soft delete)
    Route::post('/admin/{id}/restore', [AdminController::class, 'restore']);
});
