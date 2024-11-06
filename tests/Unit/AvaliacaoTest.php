<?php

use App\Models\Avaliacao;
use App\Models\Agendado;
use App\Models\Anuncio;
use App\Models\Servico;
use App\Models\TypeUser;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('Locatario avaliar locação ', function () {

    $locatario = User::factory()->create();
    TypeUser::create(['user_id' => $locatario->id, 'tipousu' => 'locatario']);
    $this->actingAs($locatario);

    $locador = User::factory()->create();
    TypeUser::create(['user_id' => $locador->id, 'tipousu' => 'locador']);
    $anuncio = Anuncio::factory()->create(['user_id' => $locador->id]);

    $avaliacao = Avaliacao::factory()->create([
        'avaliavel_type' => 'Anuncio',
        'avaliavel_id' => $anuncio->id,
        'user_id' => $locatario->id,
        'nota' => 4,
        'comentario' => 'Ótima experiência!'
    ]);

    $this->assertDatabaseHas('avaliacoes', [
        'avaliavel_type' => 'Anuncio',
        'avaliavel_id' => $anuncio->id,
        'user_id' => $locatario->id,
        'nota' => 4,
        'comentario' => 'Ótima experiência!'
    ]);
});

test('Locatario avaliar locação sem comentarios', function () {

    $locatario = User::factory()->create();
    TypeUser::create(['user_id' => $locatario->id, 'tipousu' => 'locatario']);
    $this->actingAs($locatario);

    $locador = User::factory()->create();
    TypeUser::create(['user_id' => $locador->id, 'tipousu' => 'locador']);
    $anuncio = Anuncio::factory()->create(['user_id' => $locador->id]);

    $avaliacao = Avaliacao::factory()->create([
        'avaliavel_type' => 'Anuncio',
        'avaliavel_id' => $anuncio->id,
        'user_id' => $locatario->id,
        'nota' => 4,
        'comentario' => ''
    ]);

    $this->assertDatabaseHas('avaliacoes', [
        'avaliavel_type' => 'Anuncio',
        'avaliavel_id' => $anuncio->id,
        'user_id' => $locatario->id,
        'nota' => 4,
        'comentario' => ''
    ]);
});

test('Locatario tentar avaliar locação sem preencher nota ', function () {
    $locatario = User::factory()->create();
    TypeUser::create(['user_id' => $locatario->id, 'tipousu' => 'locatario']);
    $this->actingAs($locatario);

    $locador = User::factory()->create();
    TypeUser::create(['user_id' => $locador->id, 'tipousu' => 'locador']);
    $anuncio = Anuncio::factory()->create(['user_id' => $locador->id]);

    $dadosAvaliacao = [
        'avaliavel_type' => 'Anuncio',
        'avaliavel_id' => $anuncio->id,
        'user_id' => $locatario->id,
        'comentario' => 'Faltou a nota.'
    ];

    $validator = Validator::make($dadosAvaliacao, [
        'avaliavel_type' => 'required|string',
        'avaliavel_id' => 'required|integer',
        'user_id' => 'required|integer',
        'nota' => 'required|integer|min:1|max:5',
        'comentario' => 'nullable|string'
    ]);

    $this->expectException(ValidationException::class);

    $validator->validate();
});

test('Locatario avaliar prestador ', function () {

    $locatario = User::factory()->create();
    TypeUser::create(['user_id' => $locatario->id, 'tipousu' => 'locatario']);
    $this->actingAs($locatario);

    $prestador = User::factory()->create();
    TypeUser::create(['user_id' => $prestador->id, 'tipousu' => 'prestador']);

    $avaliacao = Avaliacao::factory()->create([
        'avaliavel_type' => 'Usuario',
        'avaliavel_id' => $prestador->id,
        'user_id' => $locatario->id,
        'nota' => 4,
        'comentario' => 'Ótima experiência!'
    ]);

    $this->assertDatabaseHas('avaliacoes', [
        'avaliavel_type' => 'Usuario',
        'avaliavel_id' => $prestador->id,
        'user_id' => $locatario->id,
        'nota' => 4,
        'comentario' => 'Ótima experiência!'
    ]);
});


test('Locatario avaliar prestador sem comentario ', function () {

    $locatario = User::factory()->create();
    TypeUser::create(['user_id' => $locatario->id, 'tipousu' => 'locatario']);
    $this->actingAs($locatario);

    $prestador = User::factory()->create();
    TypeUser::create(['user_id' => $prestador->id, 'tipousu' => 'prestador']);

    $avaliacao = Avaliacao::factory()->create([
        'avaliavel_type' => 'Usuario',
        'avaliavel_id' => $prestador->id,
        'user_id' => $locatario->id,
        'nota' => 4,
        'comentario' => ''
    ]);

    $this->assertDatabaseHas('avaliacoes', [
        'avaliavel_type' => 'Usuario',
        'avaliavel_id' => $prestador->id,
        'user_id' => $locatario->id,
        'nota' => 4,
        'comentario' => ''
    ]);
});

test('Locatario tentar avaliar prestador sem preencher nota ', function () {
    $locatario = User::factory()->create();
    TypeUser::create(['user_id' => $locatario->id, 'tipousu' => 'locatario']);
    $this->actingAs($locatario);

    $prestador = User::factory()->create();
    TypeUser::create(['user_id' => $prestador->id, 'tipousu' => 'prestador']);

    $dadosAvaliacao = [
        'avaliavel_type' => 'Usuario',
        'avaliavel_id' => $prestador->id,
        'user_id' => $locatario->id,
        'comentario' => 'Faltou a nota.'
    ];

    $validator = Validator::make($dadosAvaliacao, [
        'avaliavel_type' => 'required|string',
        'avaliavel_id' => 'required|integer',
        'user_id' => 'required|integer',
        'nota' => 'required|integer|min:1|max:5',
        'comentario' => 'nullable|string'
    ]);

    $this->expectException(ValidationException::class);

    $validator->validate();
});

test('Locatario tentar avaliar prestador sem estar logado', function () {

    $locatario = User::factory()->create();
    TypeUser::create(['user_id' => $locatario->id, 'tipousu' => 'locatario']);
    $this->actingAs($locatario);

    $prestador = User::factory()->create();
    TypeUser::create(['user_id' => $prestador->id, 'tipousu' => 'prestador']);

    Auth::logout();

    $response = $this->postJson('/api/avaliacoes', [
        'avaliavel_type' => 'Usuario',
        'avaliavel_id' => $prestador->id,
        'user_id' => $locatario->id,
        'nota' => 4,
        'comentario' => 'Ótima experiência!'
    ]);

    $response->assertStatus(404);
});

test('Locatario tentar avaliar anuncio sem estar logado ', function () {

    $locatario = User::factory()->create();
    TypeUser::create(['user_id' => $locatario->id, 'tipousu' => 'locatario']);
    $this->actingAs($locatario);

    $locador = User::factory()->create();
    TypeUser::create(['user_id' => $locador->id, 'tipousu' => 'locador']);
    $anuncio = Anuncio::factory()->create(['user_id' => $locador->id]);

    $response = $this->postJson('/api/avaliacoes', [
        'avaliavel_type' => 'Anuncio',
        'avaliavel_id' => $anuncio->id,
        'user_id' => $locatario->id,
        'nota' => 4,
        'comentario' => 'Ótima experiência!'
    ]);

    $response->assertStatus(404);
});

test('Prestador avaliar locatario ', function () {

    $locatario = User::factory()->create();
    TypeUser::create(['user_id' => $locatario->id, 'tipousu' => 'locatario']);
    $this->actingAs($locatario);

    $prestador = User::factory()->create();
    TypeUser::create(['user_id' => $prestador->id, 'tipousu' => 'prestador']);

    $avaliacao = Avaliacao::factory()->create([
        'avaliavel_type' => 'Usuario',
        'avaliavel_id' => $locatario->id,
        'user_id' => $prestador->id,
        'nota' => 1,
        'comentario' => 'chato!'
    ]);

    $this->assertDatabaseHas('avaliacoes', [
        'avaliavel_type' => 'Usuario',
        'avaliavel_id' => $locatario->id,
        'user_id' => $prestador->id,
        'nota' => 1,
        'comentario' => 'chato!'
    ]);
});

test('Locador avaliar locatario ', function () {

    $locatario = User::factory()->create();
    TypeUser::create(['user_id' => $locatario->id, 'tipousu' => 'locatario']);
    $this->actingAs($locatario);

    $locador = User::factory()->create();
    TypeUser::create(['user_id' => $locador->id, 'tipousu' => 'locador']);
    $anuncio = Anuncio::factory()->create(['user_id' => $locador->id]);

    $avaliacao = Avaliacao::factory()->create([
        'avaliavel_type' => 'Usuário',
        'avaliavel_id' => $locatario->id,
        'user_id' => $locador->id,
        'nota' => 4,
        'comentario' => 'Legal!'
    ]);

    $this->assertDatabaseHas('avaliacoes', [
        'avaliavel_type' => 'Usuário',
        'avaliavel_id' => $locatario->id,
        'user_id' => $locador->id,
        'nota' => 4,
        'comentario' => 'Legal!'
    ]);
});

test('Locador avaliar locatario sem comentarios', function () {

    $locatario = User::factory()->create();
    TypeUser::create(['user_id' => $locatario->id, 'tipousu' => 'locatario']);
    $this->actingAs($locatario);

    $locador = User::factory()->create();
    TypeUser::create(['user_id' => $locador->id, 'tipousu' => 'locador']);
    $anuncio = Anuncio::factory()->create(['user_id' => $locador->id]);

    $avaliacao = Avaliacao::factory()->create([
        'avaliavel_type' => 'Usuario',
        'avaliavel_id' => $locatario->id,
        'user_id' => $locador->id,
        'nota' => 4,
        'comentario' => ''
    ]);

    $this->assertDatabaseHas('avaliacoes', [
        'avaliavel_type' => 'Usuário',
        'avaliavel_id' => $locatario->id,
        'user_id' => $locador->id,
        'nota' => 4,
        'comentario' => ''
    ]);
});

test('Locador tentar avaliar locatario sem preencher nota ', function () {
    $locatario = User::factory()->create();
    TypeUser::create(['user_id' => $locatario->id, 'tipousu' => 'locatario']);
    $this->actingAs($locatario);

    $locador = User::factory()->create();
    TypeUser::create(['user_id' => $locador->id, 'tipousu' => 'locador']);
    $anuncio = Anuncio::factory()->create(['user_id' => $locador->id]);

    $dadosAvaliacao = [
        'avaliavel_type' => 'Usuário',
        'avaliavel_id' => $locatario->id,
        'user_id' => $locador->id,
        'comentario' => 'Faltou a nota.'
    ];

    $validator = Validator::make($dadosAvaliacao, [
        'avaliavel_type' => 'required|string',
        'avaliavel_id' => 'required|integer',
        'user_id' => 'required|integer',
        'nota' => 'required|integer|min:1|max:5',
        'comentario' => 'nullable|string'
    ]);

    $this->expectException(ValidationException::class);

    $validator->validate();
});

test('Locatario tentar avaliar locador sem estar logado ', function () {

    $locatario = User::factory()->create();
    TypeUser::create(['user_id' => $locatario->id, 'tipousu' => 'locatario']);
    $this->actingAs($locatario);

    $locador = User::factory()->create();
    TypeUser::create(['user_id' => $locador->id, 'tipousu' => 'locador']);
    $anuncio = Anuncio::factory()->create(['user_id' => $locador->id]);

    $response = $this->postJson('/api/avaliacoes', [
        'avaliavel_type' => 'Anuncio',
        'avaliavel_id' => $locatario->id,
        'user_id' => $locador->id,
        'nota' => 3,
        'comentario' => 'é!'
    ]);

    $response->assertStatus(404);
});
