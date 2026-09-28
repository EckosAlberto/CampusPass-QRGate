<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLAS = ['users', 'usuarios_eventos', 'usuarios_graduacion'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->string('apellido_paterno', 100)->nullable()->after('name');
                $table->string('apellido_materno', 100)->nullable()->after('apellido_paterno');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn(['apellido_paterno', 'apellido_materno']);
            });
        }
    }
};
