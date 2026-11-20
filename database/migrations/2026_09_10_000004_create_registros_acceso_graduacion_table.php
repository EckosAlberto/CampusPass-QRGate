<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_acceso_graduacion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fk_id_boleto');
            $table->dateTime('fecha_hora');

            $table->foreign('fk_id_boleto', 'fk_registros_acceso_graduacion_boleto')
                ->references('id')->on('boletos_graduacion')
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_acceso_graduacion');
    }
};
