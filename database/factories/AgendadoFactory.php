<?php

namespace Database\Factories;

use App\Models\Servico;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\Agendado;
use App\Models\Anuncio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agendado>
 */
class AgendadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();
        TypeUser::create(['id' => $user->id, 'tipousu' => 'locatario']);
        $servico = Servico::factory()->create();
        $anuncio = Anuncio::factory()->create();

        $dataInicio = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $dataFim = $this->faker->dateTimeBetween($dataInicio, '+1 month');

        return [
            'anuncio_id' => $anuncio->id,
            'formapagamento'=> $this->faker->sentence,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
        ];
    }
    public function configure()
    {
        return $this->afterCreating(function (Agendado $agendado) {
            $servico = Servico::inRandomOrder()->first();
           $agendado->servico()->attach($servico->id);
        });
    }
}
