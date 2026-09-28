<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencias_acceso_graduacion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fk_id_ceremonia')->nullable();
            $table->string('codigo', 40)->nullable();
            $table->string('tipo', 20);
            $table->text('mensaje')->nullable();
            $table->dateTime('fecha_hora');

            $table->foreign('fk_id_ceremonia', 'fk_incidencias_acceso_graduacion_ceremonia')
                ->references('id')->on('ceremonias_graduacion')
                ->onUpdate('cascade')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias_acceso_graduacion');
    }
};
