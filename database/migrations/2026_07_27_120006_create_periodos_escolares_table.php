<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_escolares', function (Blueprint $table) {
            $table->char('periodo', 5)->primary();
            $table->char('identificacion_larga', 30)->nullable();
            $table->char('identificacion_corta', 12)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_escolares');
    }
};
