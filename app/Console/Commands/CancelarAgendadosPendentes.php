<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agendado;
use Carbon\Carbon;


// Para rodar a rotina de cancelamentos manualmente rodar comando php artisan agendados:cancelar-pendentes
class CancelarAgendadosPendentes extends Command
{
    protected $signature = 'agendados:cancelar-pendentes';
    protected $description = 'Cancela agendados pendentes de pagamento a 5 dias da data de início';

    public function handle()
    {
        // data limite: 5 dias a partir da data atual
        $dataLimite = Carbon::now()->addDays(5);


        $agendadosPendentes = Agendado::where('status_pagamento', 'pendente')
            ->where('data_inicio', '<=', $dataLimite)
            ->get();

        foreach ($agendadosPendentes as $agendado) {
            $agendado->status_pagamento = 'cancelado';
            $agendado->save();

            \Log::channel('logagendados')->info("Agendado #{$agendado->id} cancelado por falta de pagamento.");
        }

        $this->info('Verificação concluída e agendados pendentes foram cancelados se necessário.');
    }
}

