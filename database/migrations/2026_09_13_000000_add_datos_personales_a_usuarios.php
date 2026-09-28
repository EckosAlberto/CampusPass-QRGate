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
                $table->date('fecha_nacimiento')->nullable()->after('activo');
                $table->string('curp', 18)->nullable()->after('fecha_nacimiento');
                $table->string('rfc', 13)->nullable()->after('curp');
                $table->string('telefono', 15)->nullable()->after('rfc');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn(['fecha_nacimiento', 'curp', 'rfc', 'telefono']);
            });
        }
    }
};
