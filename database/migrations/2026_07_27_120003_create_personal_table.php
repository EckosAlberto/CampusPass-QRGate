<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal', function (Blueprint $table) {
            $table->char('rfc', 13)->primary();
            $table->char('curp_empleado', 18)->nullable();
            $table->string('apellidos_empleado', 45)->nullable();
            $table->string('nombre_empleado', 35)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal');
    }
};
