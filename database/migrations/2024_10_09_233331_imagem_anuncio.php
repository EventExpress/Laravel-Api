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
        Schema::create('imagem_anuncios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anuncio_id')->constrained()->onDelete('cascade');
            $table->longText('image_path')->change();
            $table->boolean('is_main')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imagem_anuncios');
    }
};
