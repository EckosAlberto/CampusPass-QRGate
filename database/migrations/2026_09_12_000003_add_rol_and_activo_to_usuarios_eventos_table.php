<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios_eventos', function (Blueprint $table) {
            $table->uuid('rol_id')->nullable()->after('email');
            $table->boolean('activo')->default(true)->after('rol_id');

            $table->foreign('rol_id', 'fk_usuarios_eventos_rol')
                ->references('id')->on('roles_eventos')
                ->onUpdate('cascade')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios_eventos', function (Blueprint $table) {
            $table->dropForeign('fk_usuarios_eventos_rol');
            $table->dropColumn(['rol_id', 'activo']);
        });
    }
};
