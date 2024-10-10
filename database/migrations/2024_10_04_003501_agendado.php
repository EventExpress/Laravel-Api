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
        Schema::create('agendados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('anuncio_id')->constrained()->onDelete('cascade');
            $table->foreignId('comprovante_id')->constrained()->onDelete('cascade');
            $table->softDeletes();
            $table->string('formapagamento', 50);
            $table->datetime('data_inicio');
            $table->datetime('data_fim');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendados');
    }
};
