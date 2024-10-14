<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgendadoServicoTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agendado_servico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agendado_id');
            $table->unsignedBigInteger('servico_id');
            $table->foreign('agendado_id')->references('id')->on('agendados')->onDelete('cascade');
            $table->foreign('servico_id')->references('id')->on('servicos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendado_servico');
    }
};
