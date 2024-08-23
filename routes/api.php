<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::post('/register', [UserController::class, 'store']);

Route::post('/login', function (Request $request) {

    $credentials = $request->only('email','password');

    if(Auth::attempt($credentials)){
        $user = $request->user();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 401);
    }

    return response()->json([
        'message' => 'Usuario Invalido',
    ]);

});

Route::middleware('auth:sanctum')->get('/user/profile', function (Request $request) {
   return $request->user();
});

