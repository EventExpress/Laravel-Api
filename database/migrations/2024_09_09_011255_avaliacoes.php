<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->morphs('avaliavel'); // Cria as colunas 'avaliavel_type' e 'avaliavel_id'
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Definir a chave estrangeira para a tabela de usuários
            $table->integer('nota'); // Por exemplo, uma nota de 1 a 5
            $table->text('comentario')->nullable(); // Comentário opcional
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};
