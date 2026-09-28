<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_biblioteca', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_sistema', 100)->default('CampusPass');
            $table->string('institucion', 150)->default('Tecnológico Nacional de México');
            $table->string('nombre_biblioteca', 100)->default('Biblioteca Central');
            $table->unsignedInteger('capacidad_maxima')->default(100);
            $table->time('horario_apertura')->default('08:00');
            $table->time('horario_cierre')->default('19:00');
            $table->boolean('qr_activo')->default(true);
            $table->unsignedInteger('qr_validez_minutos')->default(5);
            $table->boolean('qr_permitir_reingreso')->default(true);
            $table->boolean('qr_notificar_correo')->default(false);
            $table->boolean('notif_incidencias')->default(true);
            $table->boolean('notif_reportes')->default(true);
            $table->boolean('notif_resumen_diario')->default(false);
            $table->boolean('notif_correo')->default(false);
            $table->unsignedInteger('sesion_inactividad_minutos')->default(30);
            $table->boolean('sesion_cerrar_auto')->default(true);
            $table->boolean('sesion_mantener_activa')->default(true);
            $table->string('zona_horaria', 60)->default('America/Mazatlan');
            $table->string('idioma', 5)->default('es');
            $table->string('formato_fecha', 20)->default('DD/MM/AAAA');
            $table->string('nivel_registro', 20)->default('info');
            $table->boolean('modo_mantenimiento')->default(false);
            $table->boolean('canal_correo_activo')->default(true);
            $table->boolean('canal_push_activo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_biblioteca');
    }
};
