<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_biblioteca', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 120);
            $table->string('tipo', 20);
            $table->unsignedInteger('fk_id_ubicacion')->nullable();
            $table->char('carrera', 3)->nullable();
            $table->integer('semestre')->nullable();
            $table->char('sexo', 1)->nullable();
            $table->string('tipo_movimiento', 10)->nullable();
            $table->date('fecha_inicial');
            $table->date('fecha_final');
            $table->integer('registros')->default(0);
            $table->json('datos');
            $table->foreignId('fk_generado_por')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('fk_id_ubicacion', 'fk_reportes_biblioteca_ubicacion')
                ->references('id')->on('ubicaciones')
                ->onUpdate('cascade')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_biblioteca');
    }
};
