<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodo_tutorias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('clave', 64)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->char('periodo', 5)->nullable();
            $table->boolean('cierre')->default(false);

            $table->foreign('periodo', 'fk_periodo_tutorias_periodo')
                ->references('periodo')->on('periodos_escolares')
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodo_tutorias');
    }
};
