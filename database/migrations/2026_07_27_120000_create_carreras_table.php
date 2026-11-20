<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carreras', function (Blueprint $table) {
            $table->char('carrera', 3);
            $table->integer('reticula');
            $table->string('nombre_carrera', 80)->nullable();
            $table->string('nombre_reducido', 30)->nullable();
            $table->string('siglas', 10)->nullable();

            $table->primary(['carrera', 'reticula']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carreras');
    }
};
