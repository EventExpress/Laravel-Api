<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Registro de usuário
Route::post('/register', [UserController::class, 'store']);

// Login do usuário
Route::post('/login', [AuthController::class, 'login']);

// Logout
Route::middleware('auth:sanctum')->post('/user/logout',[AuthController::class, 'logout']);

// Rota protegida para obter informações do usuário
Route::middleware('auth:sanctum')->get('/user/profile', function (Request $request) {
    return $request->user();
});
