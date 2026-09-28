<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_graduacion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 120);
            $table->string('tipo', 20);
            $table->uuid('fk_id_ceremonia')->nullable();
            $table->char('carrera', 3)->nullable();
            $table->date('fecha_inicial');
            $table->date('fecha_final');
            $table->integer('registros')->default(0);
            $table->json('datos');
            $table->foreignId('fk_generado_por')->nullable()
                ->constrained('usuarios_graduacion')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('fk_id_ceremonia', 'fk_reportes_graduacion_ceremonia')
                ->references('id')->on('ceremonias_graduacion')
                ->onUpdate('cascade')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_graduacion');
    }
};
