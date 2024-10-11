<?php

use App\Models\Categoria;
use App\Models\TypeUser;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);


test('cadastro de novo anuncio com todos os campos corretamente', function () {
    $this->seed(CategoriaSeeder::class);

    $user = User::factory()->create();

    //associa tipo ao usuário
    TypeUser::create(['user_id' => $user->id, 'tipousu' => 'locador']);

    //autentica o usuário
    $this->actingAs($user);

    $response = $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => '2024-12-12',
        'categoriaId' => [1, 2],
    ]);

    $response->assertStatus(201)
             ->assertJson([
                 'status' => true,
                 'message' => 'Anúncio criado com sucesso.',
             ]);
});


test('preencher campos obrigatórios incorretamente', function () {
    $this->seed(CategoriaSeeder::class);

    $user = User::factory()->create();
    TypeUser::create(['user_id' => $user->id, 'tipousu' => 'locador']);
    $this->actingAs($user);

    $response = $this->postJson('/api/anuncios', [
        'titulo' => '', //campo vazio
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => '2024-12-12',
        'categoriaId' => [1, 2],
    ]);

    $response->assertStatus(422)
             ->assertJsonStructure([
                 'message',
                 'errors' => [
                     'titulo',
                 ],
             ]);
});


test('pesquisar anuncio com termos válidos', function () {
    $this->seed(CategoriaSeeder::class);

    $user = User::factory()->create();
    TypeUser::create(['user_id' => $user->id, 'tipousu' => 'locador']);
    $this->actingAs($user);
    
    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => '2024-12-12',
        'categoriaId' => [1, 2],
    ]);

    $response = $this->getJson('/api/anuncios?search=Festa');

    $response->assertStatus(200)
             ->assertJsonFragment(['titulo' => 'Festa de Casamento']);
});



test('pesquisar anuncio com termos inválidos', function () {
    $this->seed(CategoriaSeeder::class);

    $user = User::factory()->create();
    TypeUser::create(['user_id' => $user->id, 'tipousu' => 'locador']);
    $this->actingAs($user);

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => '2024-12-12',
        'categoriaId' => [1, 2],
    ]);

    $response = $this->getJson('/api/anuncios?search=Aniversario');

    $response->assertStatus(200)
             ->assertJson([
                 'status' => true,
                 'anuncios' => [],
             ]);
});

test('pesquisar todos os anuncios do locador', function () {
    $this->seed(CategoriaSeeder::class);

    $user = User::factory()->create();
    TypeUser::create(['user_id' => $user->id, 'tipousu' => 'locador']);
    $this->actingAs($user);

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => '2024-12-12',
        'categoriaId' => [1, 2],
    ]);

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Aniversário',
        'cidade' => 'Curitiba',
        'cep' => '12345-679',
        'numero' => 456,
        'bairro' => 'Batel',
        'capacidade' => 200,
        'descricao' => 'Um local perfeito para festas de aniversário.',
        'valor' => 3000,
        'agenda' => '2025-12-12',
        'categoriaId' => [1,2],
    ]);

    $response = $this->getJson('/api/anuncios?meus');

    $response->assertStatus(200)
        ->assertJsonFragment(['titulo' => 'Festa de Casamento'])
        ->assertJsonFragment(['titulo' => 'Festa de Aniversário'])
        ->assertJson([
            'status' => true,
        ]);
});

test('pesquisar por anuncio especifico do locador', function () {
    $this->seed(CategoriaSeeder::class);

    $user = User::factory()->create();
    TypeUser::create(['user_id' => $user->id, 'tipousu' => 'locador']);
    $this->actingAs($user);

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => '2024-12-12',
        'categoriaId' => [1, 2],
    ]);

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Aniversário',
        'cidade' => 'Curitiba',
        'cep' => '12345-679',
        'numero' => 456,
        'bairro' => 'Batel',
        'capacidade' => 200,
        'descricao' => 'Um local perfeito para festas de aniversário.',
        'valor' => 3000,
        'agenda' => '2025-12-12',
        'categoriaId' => [1,2],
    ]);

    $response = $this->getJson('/api/anuncios?meus?search=Casamento');

    $response->assertStatus(200)
        ->assertJsonFragment(['titulo' => 'Festa de Casamento'])
        ->assertJson([
            'status' => true,
        ]);
});

test('pesquisar por anuncio inexistente do locador', function () {
    $this->seed(CategoriaSeeder::class);

    $user = User::factory()->create();
    TypeUser::create(['user_id' => $user->id, 'tipousu' => 'locador']);
    $this->actingAs($user);

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => '2024-12-12',
        'categoriaId' => [1, 2],
    ]);

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Aniversário',
        'cidade' => 'Curitiba',
        'cep' => '12345-679',
        'numero' => 456,
        'bairro' => 'Batel',
        'capacidade' => 200,
        'descricao' => 'Um local perfeito para festas de aniversário.',
        'valor' => 3000,
        'agenda' => '2025-12-12',
        'categoriaId' => [1,2],
    ]);

    $response = $this->getJson('/api/anuncios?meus?search=infantil');

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'anuncios' => [],
        ]);
});










