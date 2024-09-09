<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Rotas públicas (registro e login)
Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', [AuthController::class, 'profile']);

    // Rotas para operações CRUD de usuários
    Route::get('/user/{id}', [UserController::class, 'show']);
    Route::put('/user/{id}', [UserController::class, 'update']);
    Route::delete('/user/{id}', [UserController::class, 'destroy']); //softdelet para não perder dados/historico

});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin', function () {
        return "Hello Admin";
    });
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('api.admin.dashboard');

    Route::post('/user/{id}/restore', [UserController::class, 'restore']); // restaura os dados "excluidos" se for um administrador
});

