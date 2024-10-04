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
        Schema::create('avaliacao_anuncio', function (Blueprint $table) {
            $table->unsignedBigInteger('avaliacao_id');
            $table->unsignedBigInteger('anuncio_id');
            $table->foreign('anuncio_id')->references('id')->on('anuncios')->onDelete('cascade');
            $table->foreign('avaliacao_id')->references('id')->on('avaliacoes')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaliacao_anuncio');
    }
};
