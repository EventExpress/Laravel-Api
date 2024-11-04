<?php

namespace App\Http\Controllers;

use App\Models\Agendado;
use Illuminate\Http\JsonResponse;

class DashboardAdmin extends Controller
{
    public function getLocacoesPorMes(): JsonResponse
    {
        $locacoesPorMes = Agendado::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc') // Ordena por ano em ordem ascendente
            ->orderBy('month', 'asc') // Ordena por mês em ordem ascendente
            ->get();

        $result = [];
        foreach ($locacoesPorMes as $locacao) {
            $result[] = [
                'year' => $locacao->year,
                'month' => $locacao->month,
                'count' => $locacao->count,
            ];
        }

        return response()->json($result);
    }
}
