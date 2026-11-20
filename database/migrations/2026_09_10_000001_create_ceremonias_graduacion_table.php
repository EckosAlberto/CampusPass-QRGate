<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ceremonias_graduacion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 120);
            $table->char('periodo', 5)->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->string('tipo_acceso', 20)->default('qr');
            $table->boolean('estatus')->default(true);
            $table->text('descripcion')->nullable();
            $table->integer('minutos_anticipados_graduados')->default(10);
            $table->integer('minutos_anticipados_invitados')->default(10);
            $table->integer('duracion_horas_acceso')->default(5);
            $table->integer('invitados_por_defecto')->default(2);
            $table->foreignId('creado_por')->nullable()
                ->constrained('usuarios_graduacion')->nullOnDelete();
            $table->timestamps();

            $table->foreign('periodo', 'fk_ceremonias_graduacion_periodo')
                ->references('periodo')->on('periodos_escolares')
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ceremonias_graduacion');
    }
};
