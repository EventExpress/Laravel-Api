<?php

namespace Database\Factories;

use App\Models\Avaliacao;
use App\Models\Scategoria;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Servico>
 */
class ServicoFactory extends Factory
{
    protected $model = Servico::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();
  
        return [
            'titulo' => $this->faker->text(80),
            'cidade' => $this->faker->city,
            'bairro' => $this->faker->streetName,
            'descricao'=> $this->faker->text(30),
            'user_id'=>$user->id,
            'valor'=> $this->faker->randomFloat(2, 10, 1000),
            'agenda' => json_encode(['data' => '2025-09-18']),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Servico $servico) {
            // Seleciona categorias aleatórias para associar ao anúncio
            $scategorias = Scategoria::inRandomOrder()->take(rand(1, 3))->pluck('id');
            $servico->scategorias()->attach($scategorias);

            $usuarioId = User::inRandomOrder()->first()->id; // Obtém um usuário aleatório

            // Cria uma avaliação associada a esse usuário
            $avaliacao = Avaliacao::factory()->make(); // Cria uma avaliação
            $avaliacao->avaliavel_type = "Servico"; // Define o tipo polimórfico
            $avaliacao->avaliavel_id = $servico->id; // Associa à ID do anúncio
            $avaliacao->user_id = $usuarioId; // Associa o ID do usuário que fez a avaliação
            $avaliacao->save(); // Salva a avaliação
        });
    }
}
