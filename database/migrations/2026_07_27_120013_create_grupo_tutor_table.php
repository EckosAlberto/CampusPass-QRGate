<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupo_tutor', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fk_id_tutor')->nullable();
            $table->char('no_de_control', 10)->nullable();

            $table->unique(['fk_id_tutor', 'no_de_control'], 'uk_grupo_tutor_alumno');

            $table->foreign('fk_id_tutor', 'fk_grupo_tutor_tutor')
                ->references('id')->on('tutor')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('no_de_control', 'fk_grupo_tutor_alumno')
                ->references('no_de_control')->on('alumnos')
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupo_tutor');
    }
};
