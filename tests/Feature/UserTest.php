<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Models\TypeUser;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;


it('criar usuario e deslogar', function()
{
    $response = $this->postJson('/api/register', [
        'nome' => 'Teste',
        'sobrenome' => 'Usuário',
        'telefone' => '41988976119',
        'datanasc' => '2002-02-13',
        'email' => 'testeusu@gmail.com',
        'password' => 'senhaSegura123',
        'password_confirmation' => 'senhaSegura123',
        'tipousu' => ['Locatario'],
        'cpf' => '13232143212',
        'cnpj' => '',
        'cidade' => 'Curitiba',
        'cep' => '81925-187',
        'numero' => 199,
        'bairro' => 'Sitio Cercado',
    ]);

    $response->assertStatus(201)
             ->assertJson(['message' => 'Usuário criado com sucesso!']);

    //recupera o usuario criado
    $user = User::where('email', 'testeusu@gmail.com')->first();

    $this->assertNotNull($user);
    
    $this->actingAs($user);

    //o usuário está autenticado
    $this->assertTrue(Auth::check());

    Auth::logout();

    //o usuário está deslogado
    $this->assertFalse(Auth::check());

    $response = $this->getJson('/api/user/profile');

    $response->assertStatus(401)
    ->assertJson([
        'message' => 'Unauthenticated.',
    ]);

});


it('erro de servidor ao cadastrar', function () {
    // Simula a ausencia de internet através de Mockery
    $this->mock(UserController::class, function ($mock) {
        $mock->shouldReceive('store')
            ->andThrow(new \Exception('Erro 500 - Problema interno do servidor, tente novamente mais tarde'));
    });

    $response = $this->postJson('/api/register', [
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'message' => 'Erro 500 - Problema interno do servidor, tente novamente mais tarde',
        'exception' => 'Exception',
    ]);
});


it('erro de servidor ao logar', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->mock(AuthController::class, function ($mock) {
        $mock->shouldReceive('login')
            ->andThrow(new \Exception('Erro 500 - Problema interno do servidor, tente novamente mais tarde'));
    });

    $response = $this->postJson('/api/login', [
    ]);
    
    $response->assertStatus(500);
    $response->assertJson([
        'message' => 'Erro 500 - Problema interno do servidor, tente novamente mais tarde',
        'exception' => 'Exception',
    ]);
});