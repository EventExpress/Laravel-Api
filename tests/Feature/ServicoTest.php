<?php

use App\Models\Servico;
use App\Models\TypeUser;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('tentar cadastro de servico sem estar logado', function () {

    $user = User::factory()->create();

    $this->assertNotNull($user);
    
    $this->actingAs($user);

    //o usuário está autenticado
    $this->assertTrue(Auth::check());

    Auth::logout();

    //o usuário está deslogado
    $this->assertFalse(Auth::check());

    $response = $this->getJson('/api/servicos/create');

    $response->assertStatus(401)
    ->assertJson([
        'message' => 'Unauthenticated.',
    ]);
});

it('tentar pesquisar servico sem estar logado', function () {

    $user = User::factory()->create();

    $this->assertNotNull($user);
    
    $this->actingAs($user);

    //o usuário está autenticado
    $this->assertTrue(Auth::check());

    Auth::logout();

    //o usuário está deslogado
    $this->assertFalse(Auth::check());

    $response = $this->getJson('/api/servicos/search');

    $response->assertStatus(401)
    ->assertJson([
        'message' => 'Unauthenticated.',
    ]);
});

it('tentar editar servico sem estar logado', function () {

    $user = User::factory()->create();

    $this->assertNotNull($user);
    
    $this->actingAs($user);

    $servico = Servico::factory()->create();

    //o usuário está autenticado
    $this->assertTrue(Auth::check());

    Auth::logout();

    //o usuário está deslogado
    $this->assertFalse(Auth::check());

    $response = $this->getJson("/api/servicos/{$servico->id}");

    $response->assertStatus(401)
    ->assertJson([
        'message' => 'Unauthenticated.',
    ]);
});

it('tentar excluir servico sem estar logado', function () {

    $user = User::factory()->create();

    $this->assertNotNull($user);
    
    $this->actingAs($user);

    $servico = Servico::factory()->create();

    //o usuário está autenticado
    $this->assertTrue(Auth::check());

    Auth::logout();

    //o usuário está deslogado
    $this->assertFalse(Auth::check());

    $response = $this->deleteJson("/api/servicos/{$servico->id}");

    $response->assertStatus(401)
    ->assertJson([
        'message' => 'Unauthenticated.',
    ]);
});


