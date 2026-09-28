<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('clave_grupo', 10)->nullable();
            $table->uuid('fk_id_tipo_tutor')->nullable();
            $table->uuid('fk_id_periodo_tutorias')->nullable();
            $table->char('carrera', 3)->nullable();
            $table->integer('reticula')->nullable();
            $table->char('rfc', 13)->nullable();

            $table->foreign('fk_id_tipo_tutor', 'fk_tutor_tipo_tutor')
                ->references('id')->on('tipo_tutor')
                ->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('fk_id_periodo_tutorias', 'fk_tutor_periodo_tutorias')
                ->references('id')->on('periodo_tutorias')
                ->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['carrera', 'reticula'], 'fk_tutor_carrera')
                ->references(['carrera', 'reticula'])->on('carreras')
                ->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('rfc', 'fk_tutor_personal')
                ->references('rfc')->on('personal')
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor');
    }
};
