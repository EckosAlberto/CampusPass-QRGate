<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_acceso', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('no_de_control', 10)->nullable();
            $table->unsignedInteger('id_ubicacion')->nullable();
            $table->uuid('id_evento')->nullable();
            $table->string('tipo_movimiento', 10)->nullable();
            $table->dateTime('fecha_hora')->nullable();
            $table->text('observaciones')->nullable();

            $table->foreign('no_de_control', 'fk_registro_acceso_alumno')
                ->references('no_de_control')->on('alumnos')
                ->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('id_ubicacion', 'fk_registro_acceso_ubicacion')
                ->references('id')->on('ubicaciones')
                ->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('id_evento', 'fk_registro_acceso_evento')
                ->references('id')->on('evento_tutorias')
                ->onUpdate('cascade')->onDelete('restrict');
        });


        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE registro_acceso ADD CONSTRAINT chk_registro_tipo_movimiento CHECK (tipo_movimiento IN ('ENTRADA', 'SALIDA'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_acceso');
    }
};
