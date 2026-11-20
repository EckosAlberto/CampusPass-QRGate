<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencias_acceso', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('no_de_control', 10)->nullable();
            $table->unsignedInteger('id_ubicacion')->nullable();
            $table->string('tipo', 20);
            $table->text('mensaje')->nullable();
            $table->dateTime('fecha_hora');

            $table->foreign('id_ubicacion', 'fk_incidencias_acceso_ubicacion')
                ->references('id')->on('ubicaciones')
                ->onUpdate('cascade')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias_acceso');
    }
};
