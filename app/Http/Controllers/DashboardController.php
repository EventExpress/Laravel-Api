<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Agendado;
use App\Models\Servico;

class DashboardController extends Controller
{
    public function getAnuncios()
    {
        $anunciosPorCategoria = Categoria::withCount('anuncios')->get();

        return response()->json($anunciosPorCategoria);
    }


    public function getAgendados()
    {
        $agendadosPorMes = Agendado::selectRaw('DATE_FORMAT(data_inicio, "%Y-%m") as mes, COUNT(*) as quantidade')
            ->groupBy('mes')
            ->orderBy('mes') //meses retornados em ordem
            ->get();

        return response()->json($agendadosPorMes);
    }

    public function getServicos()
    {
        $servicosMaisLocados = Servico::withCount('agendados')
        ->orderBy('agendados_count', 'desc')
        ->get();

        return response()->json($servicosMaisLocados);
    }

}
