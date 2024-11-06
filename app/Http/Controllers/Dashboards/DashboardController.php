<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Agendado;
use App\Models\Anuncio;
use App\Models\Categoria;
use App\Models\Scategoria;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function relatorioCategoriasMaisReservadas() // tive que fazer a consulta manualmente pois não consegui acessar a tabela servico_cateogoria
    {
        // Obter a contagem de categorias por serviços
        $categoriasMaisReservadas = DB::table('scategorias')
            ->select('scategorias.titulo', DB::raw('COUNT(servico_scategoria.scategoria_id) as total_reservas'))
            ->join('servico_scategoria', 'scategorias.id', '=', 'servico_scategoria.scategoria_id')
            ->join('servicos', 'servico_scategoria.servico_id', '=', 'servicos.id')
            ->leftJoin('agendado_servico', 'servicos.id', '=', 'agendado_servico.servico_id')
            ->groupBy('scategorias.id')
            ->orderBy('total_reservas', 'desc')
            ->get();

        if ($categoriasMaisReservadas->isEmpty()) {
            return response()->json([
                'message' => 'Nenhuma categoria reservada encontrada.',
            ]);
        }

        $relatorio = $categoriasMaisReservadas->map(function ($item) {
            return [
                'titulo' => $item->titulo,
                'total_reservas' => $item->total_reservas,
            ];
        });

        return response()->json($relatorio);
    }

    public function relatorioReservasAnuais(Request $request)
    {
        $user = Auth::user();

        $anoAtual = now()->year;

        $anunciosIds = Anuncio::where('user_id', $user->id)->pluck('id');

        $reservas = Agendado::whereIn('anuncio_id', $anunciosIds)
            ->whereYear('data_inicio', $anoAtual)
            ->with('anuncio')
            ->get();

        $relatorio = [];

        foreach ($reservas as $reserva) {
            $mes = Carbon::parse($reserva->data_inicio)->format('F');
            $valorAnuncio = $reserva->anuncio->valor;

            $dataInicio = Carbon::parse($reserva->data_inicio);
            $dataFim = Carbon::parse($reserva->data_fim);
            $numeroDeDias = $dataInicio->diffInDays($dataFim) + 1;

            $totalMensal = $valorAnuncio * $numeroDeDias;

            if (!isset($relatorio[$mes])) {
                $relatorio[$mes] = [
                    'total' => 0,
                    'quantidade' => 0,
                ];
            }

            $relatorio[$mes]['total'] += $totalMensal;
            $relatorio[$mes]['quantidade']++;
        }

        return response()->json($relatorio);
    }

    public function relatorioReservasMensais(Request $request) //retorna quantidade de reservas que houve no mÊs
    {
        $user = Auth::user();

        $mesAtual = now()->month;
        $anoAtual = now()->year;

        $anunciosIds = Anuncio::where('user_id', $user->id)->pluck('id');

        $reservas = Agendado::whereIn('anuncio_id', $anunciosIds)
            ->whereMonth('data_inicio', $mesAtual)
            ->whereYear('data_inicio', $anoAtual)
            ->with('anuncio')
            ->get();

        $quantidadeReservas = $reservas->count();
        $lucro = $reservas->sum(function ($reserva) {
            return $reserva->anuncio->valor * (Carbon::parse($reserva->data_inicio)->diffInDays(Carbon::parse($reserva->data_fim)) + 1);
        });

        return response()->json([
            'quantidade_reservas' => $quantidadeReservas,
            'lucro' => $lucro,
        ]);
    }

}
