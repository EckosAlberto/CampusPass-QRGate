<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acceso', function (Blueprint $table) {
            $table->char('usuario', 30)->primary();
            $table->string('nombre_usuario', 100);
            $table->text('contrasena');
            $table->char('tipo_usuario', 3);
            $table->char('status', 1)->default('A');
            $table->unsignedBigInteger('fk_id_users')->nullable();

            $table->foreign('fk_id_users', 'fk_acceso_users')
                ->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('set null');
            $table->foreign('tipo_usuario', 'fk_acceso_tipo_usuario')
                ->references('tipo_usuario')->on('tipos_usuario')
                ->onUpdate('cascade')->onDelete('restrict');
        });


        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE acceso ADD CONSTRAINT chk_acceso_status CHECK (status IN ('A', 'I'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('acceso');
    }
};
