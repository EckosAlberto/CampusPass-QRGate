<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_usuario_permiso', function (Blueprint $table) {
            $table->id();
            $table->char('tipo_usuario', 3);
            $table->string('permiso_id', 40);

            $table->foreign('tipo_usuario', 'fk_tipo_usuario_permiso_tipo')
                ->references('tipo_usuario')->on('tipos_usuario')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('permiso_id', 'fk_tipo_usuario_permiso_permiso')
                ->references('id')->on('permisos_biblioteca')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->unique(['tipo_usuario', 'permiso_id'], 'uq_tipo_usuario_permiso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_usuario_permiso');
    }
};
