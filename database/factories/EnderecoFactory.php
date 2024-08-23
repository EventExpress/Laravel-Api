<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use function Pest\Faker\fake;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Endereco>
 */
class EnderecoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cidade' => $this->faker->city,
            'cep' => $this->faker->postcode,
            'numero' => $this->faker->buildingNumber,
            'bairro' => $this->faker->streetName,
        ];
    }
}
