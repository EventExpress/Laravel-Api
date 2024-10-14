<?php

namespace Database\Factories;

use App\Models\Categoria;
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
            'titulo'=> $this->faker->sentence,
            'cidade' => $this->faker->city,
            'bairro' => $this->faker->streetName,
            'descricao'=> $this->faker->text(30),
            'user_id'=>$user->id,
            'valor'=> $this->faker->randomFloat(2, 10, 1000),
            'agenda' => $this->faker->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
        ];
    }
}
