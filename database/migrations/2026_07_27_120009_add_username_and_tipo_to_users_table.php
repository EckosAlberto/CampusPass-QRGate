<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->string('username')->nullable()->after('name');
            $table->char('tipo', 3)->nullable()->after('username');

            $table->unique('username', 'uk_users_username');
            $table->foreign('tipo', 'fk_users_tipo_usuario')
                ->references('tipo_usuario')->on('tipos_usuario')
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_tipo_usuario');
            $table->dropUnique('uk_users_username');
            $table->dropColumn(['username', 'tipo']);
        });
    }
};
