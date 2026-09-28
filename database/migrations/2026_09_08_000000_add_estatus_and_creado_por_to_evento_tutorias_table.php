<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evento_tutorias', function (Blueprint $table) {
            $table->boolean('estatus')->default(true);
            $table->foreignId('creado_por')->nullable()
                ->constrained('usuarios_eventos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('evento_tutorias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creado_por');
            $table->dropColumn('estatus');
        });
    }
};
