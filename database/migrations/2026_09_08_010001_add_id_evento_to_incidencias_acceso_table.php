<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidencias_acceso', function (Blueprint $table) {
            $table->uuid('id_evento')->nullable()->after('id_ubicacion');

            $table->foreign('id_evento', 'fk_incidencias_acceso_evento')
                ->references('id')->on('evento_tutorias')
                ->onUpdate('cascade')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('incidencias_acceso', function (Blueprint $table) {
            $table->dropForeign('fk_incidencias_acceso_evento');
            $table->dropColumn('id_evento');
        });
    }
};
