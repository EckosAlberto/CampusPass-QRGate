<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rol_permiso_graduacion', function (Blueprint $table) {
            $table->id();
            $table->uuid('rol_id');
            $table->string('permiso_id', 40);

            $table->foreign('rol_id', 'fk_rol_permiso_graduacion_rol')
                ->references('id')->on('roles_graduacion')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('permiso_id', 'fk_rol_permiso_graduacion_permiso')
                ->references('id')->on('permisos_graduacion')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->unique(['rol_id', 'permiso_id'], 'uq_rol_permiso_graduacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_permiso_graduacion');
    }
};
