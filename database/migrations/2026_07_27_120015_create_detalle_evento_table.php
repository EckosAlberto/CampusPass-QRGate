<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_evento', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fk_id_evento_tutorias')->nullable();
            $table->char('carrera', 3)->nullable();
            $table->integer('reticula')->nullable();

            $table->unique(['fk_id_evento_tutorias', 'carrera', 'reticula'], 'uk_detalle_evento_carrera');

            $table->foreign('fk_id_evento_tutorias', 'fk_detalle_evento')
                ->references('id')->on('evento_tutorias')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['carrera', 'reticula'], 'fk_detalle_evento_carrera')
                ->references(['carrera', 'reticula'])->on('carreras')
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_evento');
    }
};
