<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ceremonia_carrera', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fk_id_ceremonia');
            $table->char('carrera', 3);
            $table->integer('reticula');

            $table->foreign('fk_id_ceremonia', 'fk_ceremonia_carrera_ceremonia')
                ->references('id')->on('ceremonias_graduacion')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['carrera', 'reticula'], 'fk_ceremonia_carrera_carrera')
                ->references(['carrera', 'reticula'])->on('carreras')
                ->onUpdate('cascade')->onDelete('restrict');

            $table->unique(['fk_id_ceremonia', 'carrera', 'reticula'], 'uq_ceremonia_carrera');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ceremonia_carrera');
    }
};
