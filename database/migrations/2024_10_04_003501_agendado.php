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
            $table->softDeletes();
            $table->enum('formapagamento', ['cartao_credito', 'cartao_debito', 'pix', 'boleto', 'transferencia']);
            $table->decimal('valor_total', 10, 2)->nullable();
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
