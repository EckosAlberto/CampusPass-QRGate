<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excepciones_horario_biblioteca', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('fecha');
            $table->string('motivo', 150);
            $table->boolean('cerrado_todo_dia')->default(true);
            $table->time('horario_apertura')->nullable();
            $table->time('horario_cierre')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excepciones_horario_biblioteca');
    }
};
