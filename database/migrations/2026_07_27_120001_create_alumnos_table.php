<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumnos', function (Blueprint $table) {
            $table->char('no_de_control', 10)->primary();
            $table->char('carrera', 3)->nullable();
            $table->integer('reticula')->nullable();
            $table->char('estatus_alumno', 4)->nullable();
            $table->integer('semestre')->nullable();
            $table->string('apellido_paterno', 45)->nullable();
            $table->string('apellido_materno', 45)->nullable();
            $table->string('nombre_alumno', 35)->nullable();
            $table->char('curp_alumno', 18)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->char('sexo', 1)->nullable();

            $table->foreign(['carrera', 'reticula'], 'fk_alumnos_carrera')
                ->references(['carrera', 'reticula'])->on('carreras')
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};
