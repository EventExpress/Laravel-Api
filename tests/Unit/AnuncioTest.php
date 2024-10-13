<?php

use App\Models\Endereco;
use App\Models\Anuncio;
use App\Models\Categoria;
use App\Models\TypeUser;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);


test('cadastro de novo anuncio com todos os campos corretamente', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();
    if ($categorias->count() < 2) {
        $this->fail('Categorias insuficientes após rodar o seeder.');
    }

    $user = User::factory()->create();

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
        'categoriaId' => [$categorias[0]->id, $categorias[1]->id],
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
    //TypeUser::create(['user_id' => $user->id, 'tipousu' => 'locador']);
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

test('pesquisar todos os anuncios', function () {
    $this->seed(CategoriaSeeder::class);

    $user = User::factory()->create();
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

    $response = $this->getJson('/api/anuncios');

    $response->assertStatus(200)
        ->assertJsonFragment(['titulo' => 'Festa de Casamento'])
        ->assertJsonFragment(['titulo' => 'Festa de Aniversário'])
        ->assertJson([
            'status' => true,
        ]);
});

test('pesquisar por anuncio especifico', function () {
    $this->seed(CategoriaSeeder::class);

    $user = User::factory()->create();
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

    $response = $this->getJson('/api/anuncios?search=Casamento');

    $response->assertStatus(200)
        ->assertJsonFragment(['titulo' => 'Festa de Casamento'])
        ->assertJson([
            'status' => true,
        ]);
});

test('pesquisar por anuncio inexistente', function () {
    $this->seed(CategoriaSeeder::class);

    $user = User::factory()->create();
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

    $response = $this->getJson('/api/anuncios?search=infantil');

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'anuncios' => [],
        ]);
});



test('alterar anuncio com sucesso', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();
    if ($categorias->count() < 2) {
        $this->fail('Categorias insuficientes após rodar o seeder.');
    }

    $user = User::factory()->create();
    Sanctum::actingAs($user); // Autentica o usuário com Sanctum

    $endereco = Endereco::factory()->create([
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 456,
        'bairro' => 'Batel',
    ]);

    $anuncio = Anuncio::factory()->create([
        'user_id' => $user->id,
        'endereco_id' => $endereco->id,
        'titulo' => 'Festa de Aniversário',
        'capacidade' => 50,
        'descricao' => 'Local ideal para festas de aniversário.',
        'valor' => 1500,
        'agenda' => '2024-11-10',
    ]);

    // Atualiza o anúncio com os campos de endereço diretamente no payload
    $response = $this->putJson("/api/anuncios/{$anuncio->id}", [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => '2024-12-12',
        'categoriaId' => [$categorias[0]->id, $categorias[1]->id],
    ]);

    $response->assertStatus(200)
             ->assertJson([
                 'status' => true,
                 'message' => 'Anúncio atualizado com sucesso.',
             ]);
});

test('alterar anuncio sem sucesso', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();
    if ($categorias->count() < 2) {
        $this->fail('Categorias insuficientes após rodar o seeder.');
    }

    $user = User::factory()->create();
    Sanctum::actingAs($user); 

    $endereco = Endereco::factory()->create([
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 456,
        'bairro' => 'Batel',
    ]);

    $anuncio = Anuncio::factory()->create([
        'user_id' => $user->id,
        'endereco_id' => $endereco->id,
        'titulo' => 'Festa de Aniversário',
        'capacidade' => 50,
        'descricao' => 'Local ideal para festas de aniversário.',
        'valor' => 1500,
        'agenda' => '2024-11-10',
    ]);

    $response = $this->putJson("/api/anuncios/{$anuncio->id}", [
        'titulo' => '',//campo vazio
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => '2024-12-12',
        'categoriaId' => [$categorias[0]->id, $categorias[1]->id],
    ]);

   $response->assertStatus(422);

   $response->assertJsonValidationErrors(['titulo']);

   $response->assertJson([
       'message' => 'The titulo field is required.',
       ]);
});


test('excluir anuncio com sucesso', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();
    if ($categorias->count() < 2) {
        $this->fail('Categorias insuficientes após rodar o seeder.');
    }

    $user = User::factory()->create();
    Sanctum::actingAs($user); 

    $endereco = Endereco::factory()->create([
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 456,
        'bairro' => 'Batel',
    ]);

    $anuncio = Anuncio::factory()->create([
        'user_id' => $user->id,
        'endereco_id' => $endereco->id,
        'titulo' => 'Festa de Aniversário',
        'capacidade' => 50,
        'descricao' => 'Local ideal para festas de aniversário.',
        'valor' => 1500,
        'agenda' => '2024-11-10',
    ]);

    $response = $this->deleteJson("/api/anuncios/{$anuncio->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'status' => true,
                 'message' => 'Anúncio excluído com sucesso.',
             ]);

    // Verifica se o anúncio foi realmente excluído do banco de dados
    $this->assertDatabaseMissing('anuncios', [
        'id' => $anuncio->id,
        'titulo' => 'Festa de Aniversário',
    ]);

});

test('tentar excluir anuncio inexistente', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $inexistenteId = 9999; // ID arbitrário que não corresponde a nenhum anúncio

    $response = $this->deleteJson("/api/anuncios/{$inexistenteId}");

    $response->assertStatus(403)
             ->assertJson([
                'status' => false,
                'error' => 'Anúncio não encontrado ou você não tem permissão para excluí-lo.',
             ]);
});
