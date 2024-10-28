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
        Schema::create('anuncios', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
            $table->string('titulo', 80);
            $table->foreignId('endereco_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['ativo', 'inativo'])->default('inativo')->default('ativo');
            $table->string('capacidade', 50);
            $table->string('descricao', 100);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('valor', 10);
            $table->json('agenda')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anuncios');
    }
};
