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
        Schema::create('avaliacao_servico', function (Blueprint $table) {
            $table->unsignedBigInteger('avaliacoes_id');
            $table->unsignedBigInteger('servicos_id');
            $table->foreign('servicos_id')->references('id')->on('servicos')->onDelete('cascade');
            $table->foreign('avaliacoes_id')->references('id')->on('avaliacoes')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaliacao_servico');
    }
};
