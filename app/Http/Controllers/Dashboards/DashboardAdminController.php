<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Agendado;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardAdminController extends Controller
{
    public function getLocacoesPorMes(): JsonResponse
    {
        $locacoesPorMes = Agendado::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
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

    public function getLucrosPorMes(): JsonResponse
    {
        $lucrosPorMes = Agendado::selectRaw('YEAR(agendados.created_at) as year, MONTH(agendados.created_at) as month, SUM(agendados.valor_total) as total_lucro')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        $result = [];
        foreach ($lucrosPorMes as $lucro) {
            $result[] = [
                'year' => $lucro->year,
                'month' => $lucro->month,
                'total_lucro' => $lucro->total_lucro,
            ];
        }

        return response()->json($result);
    }

    public function getUsuariosPorMes(): JsonResponse
    {
        $usuariosPorMes = User::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        $result = [];
        foreach ($usuariosPorMes as $usuario) {
            $result[] = [
                'year' => $usuario->year,
                'month' => $usuario->month,
                'count' => $usuario->count,
            ];
        }

        return response()->json($result);
    }


}
