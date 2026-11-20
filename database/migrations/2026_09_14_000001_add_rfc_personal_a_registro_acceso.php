<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_acceso', function (Blueprint $table) {
            $table->char('rfc_personal', 13)->nullable()->after('no_de_control');

            $table->foreign('rfc_personal', 'fk_registro_acceso_personal')
                ->references('rfc')->on('personal')
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('registro_acceso', function (Blueprint $table) {
            $table->dropForeign('fk_registro_acceso_personal');
            $table->dropColumn('rfc_personal');
        });
    }
};
