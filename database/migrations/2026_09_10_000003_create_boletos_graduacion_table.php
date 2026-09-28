<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boletos_graduacion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fk_id_ceremonia');
            $table->char('no_de_control', 10);
            $table->string('tipo', 10);
            $table->string('codigo', 40)->unique();
            $table->integer('invitados_autorizados')->nullable();
            $table->dateTime('generado_en');

            $table->foreign('fk_id_ceremonia', 'fk_boletos_graduacion_ceremonia')
                ->references('id')->on('ceremonias_graduacion')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('no_de_control', 'fk_boletos_graduacion_alumno')
                ->references('no_de_control')->on('alumnos')
                ->onUpdate('cascade')->onDelete('restrict');

            $table->unique(['fk_id_ceremonia', 'no_de_control', 'tipo'], 'uq_boletos_graduacion_alumno_tipo');
        });


        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE boletos_graduacion ADD CONSTRAINT chk_boletos_graduacion_tipo CHECK (tipo IN ('graduado', 'invitado'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('boletos_graduacion');
    }
};
