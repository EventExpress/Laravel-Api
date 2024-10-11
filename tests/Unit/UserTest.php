<?php

use App\Models\Endereco;
use App\Models\Nome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class); // refresh cria o banco antes de cada teste / test inclue axuiliares de asserts etc...

test('registrar um usuário com todos os dados válidos', function () {
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

test('cadastro com e-mail já registrado', function () {
    // Cria um usuário inicial com o e-mail 'testeu@gmail.com'
    User::factory()->create([
        'email' => 'testeu@gmail.com',
    ]);

    $response = $this->postJson('/api/register', [
        'nome' => 'Teste',
        'sobrenome' => 'Usuário',
        'telefone' => '41988976118',
        'datanasc' => '2002-02-12',
        'email' => 'testeu@gmail.com', // E-mail duplicado
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
    
});

test('cadastro com senha e confirmação de senha diferentes', function () {
    $response = $this->postJson('/api/register', [
        'nome' => 'Teste',
        'sobrenome' => 'Usuário',
        'telefone' => '41988976119',
        'datanasc' => '2002-02-13',
        'email' => 'testeusu@gmail.com',
        'password' => 'senhaSegura123',
        'password_confirmation' => 'senhaDiferente123', // Senha diferente
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

});

test('cadastro sem preencher todos os campos obrigatórios', function () {
    $response = $this->postJson('/api/register', [
        'nome' => 'Teste',
        'sobrenome' => 'Usuário',
        'telefone' => '41988976119',
        'datanasc' => '2002-02-13',
        //'email' => 'testeusu@gmail.com' - Falta o campo 'email'
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

});

test('cadastro com formato de e-mail inválido', function () {
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

});

test('cadastro com senha abaixo do limite mínimo de caracteres', function () {
    $response = $this->postJson('/api/register', [
        'nome' => 'Teste',
        'sobrenome' => 'Usuário',
        'telefone' => '41988976119',
        'datanasc' => '2002-02-13',
        'email' => 'testeusu@gmail.com',
        'password' => '12345', // Senha abaixo de 8 caract
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

    $response->assertJsonValidationErrors(['password']);

});

test('logar com usuário ou senha inválidos', function () {
    $user = User::factory()->create([
        'email' => 'usuario@teste.com',
        'password' => bcrypt('senhaValida123') // Certifique-se de que a senha está criptografada
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'usuario@teste.com',
        'password' => 'senhaErrada123',  // Senha incorreta
    ]);

    $response->assertStatus(422);

    $response->assertJson([
        'message' => 'Validation Error',
        'errors' => [
            'email' => [
                'The provided credentials are incorrect.'
            ]
        ]
    ]);
});

test('logar com usuário e senha válidos', function () {
    $user = User::factory()->create([
        'email' => 'usuario@teste.com',
        'password' => bcrypt('senhaValida123') // Certifique-se de que a senha está criptografada
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'usuario@teste.com',
        'password' => 'senhaValida123',
    ]);

    $response->assertStatus(200);

    // Verifica se a mensagem "Authorized" e o token estão presentes
    $response->assertJson([
        'message' => 'Authorized',
        'token' => true, // Verifica se o token foi gerado
    ]);
});

test('login com SQL injection', function () {
    $user = User::factory()->create([
        'email' => 'usuario@teste.com',
        'password' => bcrypt('senhaValida123')
    ]);

    $response = $this->postJson('/api/login', [
        'email' => "usuario@teste.com' OR '1'='1",  // Tentando logar com SQL Injection
        'password' => 'senhaValida123',  
    ]);

    $response->assertStatus(422);

    $response->assertJson([
        'message' => 'Validation Error',
    ]);
});