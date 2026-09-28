<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'usuarios_eventos', 'usuarios_graduacion'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->timestamp('notificaciones_vistas_en')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['users', 'usuarios_eventos', 'usuarios_graduacion'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn('notificaciones_vistas_en');
            });
        }
    }
};
