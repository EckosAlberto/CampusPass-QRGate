<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_evento', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('clave', 6)->nullable();
            $table->string('nombre', 64)->nullable();

            $table->unique('clave', 'uk_tipo_evento_clave');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_evento');
    }
};
