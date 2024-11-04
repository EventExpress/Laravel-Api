<?php

use App\Models\Endereco;
use App\Models\Nome;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class); // refresh cria o banco antes de cada teste / test inclue axuiliares de asserts etc...

test('Cadastro de usuário com todos os dados válidos', function () {
    // Envia os dados para a rota '/api/register' com o método POST
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

    $response->assertStatus(201);

    $response->assertJson(['message' => 'Usuário criado com sucesso!']);
});

test('Tentar cadastro com e-mail já registrado', function () {
    //cria usuario com o e-mail especifico
    User::factory()->create([
        'email' => 'testeu@gmail.com',
    ]);

    $response = $this->postJson('/api/register', [
        'nome' => 'Teste',
        'sobrenome' => 'Usuário',
        'telefone' => '41988976118',
        'datanasc' => '2002-02-12',
        'email' => 'testeu@gmail.com', //email duplicado
        'password' => 'senhaSegura123',
        'password_confirmation' => 'senhaSegura123',
        'tipousu' => ['Locatario'],
        'cpf' => '13232143213',
        'cnpj' => '',
        'cidade' => 'Curitiba',
        'cep' => '81925-186',
        'numero' => 199,
        'bairro' => 'Sitio Cercado',
    ]);

    // Verifica se o status é 422 (erro de validação)
    $response->assertStatus(422);

    $response->assertJsonValidationErrors(['email']);

    $response->assertJson([
        'message' => 'The email has already been taken.',
    ]);
    
});



test('Tentar cadastro sem preencher todos os campos obrigatórios', function () {
    $response = $this->postJson('/api/register', [
        'nome' => 'Teste',
        'sobrenome' => 'Usuário',
        'telefone' => '41988976119',
        'datanasc' => '2002-02-13',
        //'email' => 'testeusu@gmail.com' - Falta o campo email
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

    $response->assertStatus(422);

    $response->assertJsonValidationErrors(['email']);

    $response->assertJson([
        'message' => 'The email field is required.',
    ]);

});

test('Tentar cadastro com formato de e-mail inválido', function () {
    $response = $this->postJson('/api/register', [
        'nome' => 'Teste',
        'sobrenome' => 'Usuário',
        'telefone' => '41988976119',
        'datanasc' => '2002-02-13',
        'email' => 'testeusu', // E-mail inválido
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

    $response->assertStatus(422);

    $response->assertJsonValidationErrors(['email']);

    $response->assertJson([
        'message' => 'The email field must be a valid email address.',
    ]);

});

test('Tentar cadastro com senha abaixo do limite mínimo de caracteres', function () {
    $response = $this->postJson('/api/register', [
        'nome' => 'Teste',
        'sobrenome' => 'Usuário',
        'telefone' => '41988976119',
        'datanasc' => '2002-02-13',
        'email' => 'testeusu@gmail.com',
        'password' => '12345', // Senha abaixo de 8 caract
        'password_confirmation' => '12345',
        'tipousu' => ['Locatario'],
        'cpf' => '13232143212',
        'cnpj' => '',
        'cidade' => 'Curitiba',
        'cep' => '81925-187',
        'numero' => 199,
        'bairro' => 'Sitio Cercado',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors(['password']);

    $response->assertJson([
        'message' => 'The password field must be at least 8 characters.',
    ]);

});

test('Logar com usuário ou senha inválidos', function () {
    $user = User::factory()->create([
        'email' => 'usuario@teste.com',
        'password' => bcrypt('senhaValida123') //a senha está criptografada
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'usuario@teste.com',
        'password' => 'senhaErrada123',  //senha incorreta
    ]);

    $response->assertStatus(422);

    $response->assertJson([
        'message' => 'Validation Error',
    ]);
});

test('Logar com usuário e senha válidos', function () {
    $user = User::factory()->create([
        'email' => 'usuario@teste.com',
        'password' => bcrypt('senhaValida123') //a senha está criptografada
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'usuario@teste.com',
        'password' => 'senhaValida123',
    ]);

    $response->assertStatus(200);//200

    //a mensagem "Authorized" e o token estão presentes
    $response->assertJson([
        'message' => 'Authorized',
        'token' => true,
    ]);
});


test('Alterar usuário com sucesso', function (){
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

    $response->assertStatus(201);

    $response->assertJson(['message' => 'Usuário criado com sucesso!']);

    $user = User::latest()->first();

    Sanctum::actingAs($user);

    $response = $this->putJson("/api/user/{$user->id}", [
        'nome' => 'TesteAtualizado',
        'sobrenome' => 'UsuárioAtualizado',
        'telefone' => '41988976119',
        'datanasc' => '2003-02-13',
        'email' => 'testeatualizado@gmail.com',
        'password' => 'senhaAtualizada123',
        'password_confirmation' => 'senhaAtuaizada123',
        'tipousu' => ['Locatario'],
        'cpf' => '13232143212',
        'cnpj' => '',
        'cidade' => 'Curitiba',
        'cep' => '81925-185',
        'numero' => 200,
        'bairro' => 'Portão',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Usuário atualizado com sucesso!']);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'nome' => 'TesteAtualizado',
    ]);
});

test('Alterar usuário sem sucesso', function (){
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

    $response->assertStatus(201);

    $response->assertJson(['message' => 'Usuário criado com sucesso!']);

    $user = User::latest()->first();

    Sanctum::actingAs($user);

    $response = $this->putJson("/api/user/{$user->id}", [
        'nome' => '',//vazio
        'sobrenome' => 'UsuárioAtualizado',
        'telefone' => '41988976119',
        'datanasc' => '2003-02-13',
        'email' => 'testeatualizado@gmail.com',
        'password' => 'senhaAtualizada123',
        'password_confirmation' => 'senhaAtuaizada123',
        'tipousu' => ['Locatario'],
        'cpf' => '13232143212',
        'cnpj' => '',
        'cidade' => 'Curitiba',
        'cep' => '81925-185',
        'numero' => 200,
        'bairro' => 'Portão',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['nome']);
});

test('Deletar usuário com sucesso', function (){
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

    $response->assertStatus(201);

    $response->assertJson(['message' => 'Usuário criado com sucesso!']);

    $user = User::latest()->first();

    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/user/{$user->id}");

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Usuário deletado com sucesso!']);
});

test('Tentar deletar usuário inexistente', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $nonExistentId = 9999;

    $response = $this->deleteJson("/api/user/{$nonExistentId}");

    $response->assertStatus(500);

});