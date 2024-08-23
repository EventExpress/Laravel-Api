<?php

namespace Database\Factories;

use App\Models\Endereco;
use App\Models\Nome;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{

    protected $model = User::class;
    public function definition(): array
    {
        $endereco = Endereco::factory()->create();
        $nome = Nome::factory()->create();
        $password = 'xxx12345';

        return [
            'nome_id' => $nome->id,
            'telefone' => $this->faker->numerify('(##)####-####'),
            'email' => $this->faker->email,
            'password' => Hash::make($password),
            'datanasc' => $this->faker->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'tipousu' => $this->faker->randomElement(['cliente', 'locador', 'prestador']),
            'cpf' => $this->faker->numerify('###.###.###-##'),
            'cnpj' => $this->faker->numerify('##.###.###/####-##'),
            'endereco_id' => $endereco->id,
        ];
    }
}
