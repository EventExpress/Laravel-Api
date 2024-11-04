<?php

namespace App\Http\Controllers;

use App\Models\Comprovante;
use App\Models\Servico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComprovanteController extends Controller
{
    // Construtor para aplicar middleware se necessário
    public function __construct()
    {
        // $this->middleware('auth'); // descomente se quiser proteger as rotas
    }

    public function index()
    {
        $comprovantes = Comprovante::all();

        $comprovantes->map(function ($comprovante) {
            if (is_string($comprovante->servicos_id)) {
                $servicosIds = json_decode($comprovante->servicos_id);
            } else {
                $servicosIds = $comprovante->servicos_id;
            }

            $comprovante->servicos = Servico::whereIn('id', $servicosIds)->get();

            return $comprovante;
        });

        return response()->json($comprovantes);
    }

    public function show()
    {
        $user = auth()->user();


        $comprovantes = Comprovante::where('user_id', $user->id)->get();

        $comprovantes->map(function ($comprovante) {
            if (is_string($comprovante->servicos_id)) {
                $servicosIds = json_decode($comprovante->servicos_id);
            } else {
                $servicosIds = $comprovante->servicos_id;
            }

            $comprovante->servicos = Servico::whereIn('id', $servicosIds)->get();

            return $comprovante;
        });

        return response()->json($comprovantes);

    }

}
