<?php

namespace Database\Factories;

use App\Models\Endereco;
use App\Models\Nome;
use App\Models\User;
use App\Models\TypeUser;
use Illuminate\Database\Eloquent\Factories\Factory;
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
            'cpf' => $this->faker->numerify('###.###.###-##'),
            'cnpj' => $this->faker->numerify('##.###.###/####-##'),
            'endereco_id' => $endereco->id,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            // Escolher um tipo de usuário aleatório para associar
            $tipoUsuario = TypeUser::inRandomOrder()->first();
        
    // Se não houver tipo de usuário, crie um tipo de usuário padrão
    if (!$tipoUsuario) {
        $tipoUsuario = TypeUser::create(); // ou outro tipo qualquer
    }

            // Associar o usuário ao tipo de usuário na tabela pivot
            $user->typeUsers()->attach($tipoUsuario->id);
        });
    }
}
