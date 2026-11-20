<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento_tutorias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 64)->nullable();
            $table->date('fecha')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->integer('horas')->nullable();
            $table->uuid('fk_id_tipo_evento')->nullable();
            $table->uuid('fk_id_periodo_tutorias')->nullable();
            $table->uuid('fk_id_tutor')->nullable();

            $table->foreign('fk_id_tipo_evento', 'fk_evento_tipo_evento')
                ->references('id')->on('tipo_evento')
                ->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('fk_id_periodo_tutorias', 'fk_evento_periodo_tutorias')
                ->references('id')->on('periodo_tutorias')
                ->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('fk_id_tutor', 'fk_evento_tutor')
                ->references('id')->on('tutor')
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_tutorias');
    }
};
