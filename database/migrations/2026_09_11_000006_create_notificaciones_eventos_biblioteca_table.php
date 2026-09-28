<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_eventos_biblioteca', function (Blueprint $table) {
            $table->string('evento', 40)->primary();
            $table->boolean('correo')->default(false);
            $table->boolean('push')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_eventos_biblioteca');
    }
};
