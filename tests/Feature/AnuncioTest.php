<?php

use App\Models\Anuncio;
use App\Models\TypeUser;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('tentar cadastro de anuncio sem estar logado', function () {

    $user = User::factory()->create();

    $this->assertNotNull($user);
    
    $this->actingAs($user);

    //o usuário está autenticado
    $this->assertTrue(Auth::check());

    Auth::logout();

    //o usuário está deslogado
    $this->assertFalse(Auth::check());

    $response = $this->getJson('/api/anuncios/create');

    $response->assertStatus(401)
    ->assertJson([
        'message' => 'Unauthenticated.',
    ]);
});

it('tentar pesquisar anuncio sem estar logado', function () {

    $user = User::factory()->create();

    $this->assertNotNull($user);
    
    $this->actingAs($user);

    //o usuário está autenticado
    $this->assertTrue(Auth::check());

    Auth::logout();

    //o usuário está deslogado
    $this->assertFalse(Auth::check());

    $response = $this->getJson('/api/anuncios/search');

    $response->assertStatus(401)
    ->assertJson([
        'message' => 'Unauthenticated.',
    ]);
});

it('tentar editar anuncio sem estar logado', function () {

    $user = User::factory()->create();

    $this->assertNotNull($user);
    
    $this->actingAs($user);

    $anuncio = Anuncio::factory()->create();

    //o usuário está autenticado
    $this->assertTrue(Auth::check());

    Auth::logout();

    //o usuário está deslogado
    $this->assertFalse(Auth::check());

    $response = $this->getJson("/api/anuncios/{$anuncio->id}");

    $response->assertStatus(401)
    ->assertJson([
        'message' => 'Unauthenticated.',
    ]);
});

it('tentar excluir anuncio sem estar logado', function () {

    $user = User::factory()->create();

    $this->assertNotNull($user);
    
    $this->actingAs($user);

    $anuncio = Anuncio::factory()->create();

    //o usuário está autenticado
    $this->assertTrue(Auth::check());

    Auth::logout();

    //o usuário está deslogado
    $this->assertFalse(Auth::check());

    $response = $this->deleteJson("/api/anuncios/{$anuncio->id}");

    $response->assertStatus(401)
    ->assertJson([
        'message' => 'Unauthenticated.',
    ]);
});