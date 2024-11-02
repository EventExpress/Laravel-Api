<?php

namespace App\Http\Controllers;

use App\Models\Comprovante;
use Illuminate\Http\Request;

class ComprovanteController extends Controller
{
    public function index()
    {
        $comprovantes = Comprovante::all();
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

    public function update(Request $request, $id)
    {
        $comprovante = Comprovante::findOrFail($id);

        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'anuncios_id' => 'nullable|exists:anuncios,id',
            'servicos_id' => 'nullable|exists:servicos,id',
        ]);

        $comprovante->update($validatedData);
        return response()->json($comprovante);
    }

    public function destroy($id)
    {
        $comprovante = Comprovante::findOrFail($id);
        $comprovante->delete();

        return response()->json(['message' => 'Comprovante deleted successfully']);
    }
}

