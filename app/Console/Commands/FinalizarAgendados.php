<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agendado;
use Carbon\Carbon;


//php artisan agendados:finalizar-reserva
class FinalizarAgendados extends Command
{
    protected $signature = 'agendados:finalizar-reserva';
    protected $description = 'Finaliza agendados cuja data de término já passou';

    public function handle()
    {
        $dataAtual = Carbon::now();

        $agendadosExpirados = Agendado::where('status_pagamento', '!=', 'finalizado')
            ->where('data_fim', '<', $dataAtual)
            ->get();

        foreach ($agendadosExpirados as $agendado) {
            $agendado->status_pagamento = 'finalizado';
            $agendado->save();

            \Log::channel('logagendados')->info("Agendado #{$agendado->id} finalizado automaticamente.");
        }

        $this->info('Verificação concluída e agendados expirados foram finalizados se necessário.');
    }
}
