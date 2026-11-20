<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios_graduacion', function (Blueprint $table) {
            $table->uuid('rol_id')->nullable()->after('email');
            $table->boolean('activo')->default(true)->after('rol_id');

            $table->foreign('rol_id', 'fk_usuarios_graduacion_rol')
                ->references('id')->on('roles_graduacion')
                ->onUpdate('cascade')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios_graduacion', function (Blueprint $table) {
            $table->dropForeign('fk_usuarios_graduacion_rol');
            $table->dropColumn(['rol_id', 'activo']);
        });
    }
};
