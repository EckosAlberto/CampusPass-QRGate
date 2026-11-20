<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_biblioteca', function (Blueprint $table) {
            $table->string('dia', 10)->primary();
            $table->boolean('abierto')->default(true);
            $table->time('apertura')->nullable();
            $table->time('cierre')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_biblioteca');
    }
};
