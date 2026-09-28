<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identificador_fotos', function (Blueprint $table) {
            $table->timestamp('fecha_registro')->nullable();
            $table->boolean('estatus')->default(true);
            $table->string('foto_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('identificador_fotos', function (Blueprint $table) {
            $table->dropColumn(['fecha_registro', 'estatus', 'foto_path']);
        });
    }
};
