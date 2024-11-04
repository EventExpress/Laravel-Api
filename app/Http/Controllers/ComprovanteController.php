<?php

namespace App\Http\Controllers;

use App\Models\Comprovante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComprovanteController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $comprovantes = Comprovante::where('user_id', $userId)->with(['user', 'anuncio', 'servico'])->get();
        return response()->json($comprovantes);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'anuncios_id' => 'nullable|exists:anuncios,id',
            'servicos_id' => 'nullable|exists:servicos,id',
        ]);

        $comprovante = Comprovante::create($validatedData);
        return response()->json($comprovante, 201);
    }

    public function show($id)
    {
        $comprovante = Comprovante::findOrFail($id);
        return response()->json($comprovante);
    }

}

