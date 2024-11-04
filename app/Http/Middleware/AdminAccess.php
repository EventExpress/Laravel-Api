<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {

            $user = auth()->user();

            $isAdmin = $user->typeUsers->contains(function ($typeUser) {
                return $typeUser->tipousu === 'admin';
            });

            if ($isAdmin) {
                return $next($request);
            }
        }

        return response()->json(['error' => 'Acesso negado, você não é um administrador'], 403);


    }
}
