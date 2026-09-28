<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_evento', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('asistencia')->default(false);
            $table->date('fecha_asistencia')->nullable();
            $table->uuid('fk_id_grupo_tutor')->nullable();
            $table->uuid('fk_id_detalle_evento')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['fk_id_grupo_tutor', 'fk_id_detalle_evento'], 'uk_registro_evento');

            $table->foreign('fk_id_grupo_tutor', 'fk_registro_evento_grupo')
                ->references('id')->on('grupo_tutor')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('fk_id_detalle_evento', 'fk_registro_evento_detalle')
                ->references('id')->on('detalle_evento')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_evento');
    }
};
