<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_tutor', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('clave', 4)->nullable();
            $table->string('nombre', 64)->nullable();
            $table->boolean('visible')->default(true);

            $table->unique('clave', 'uk_tipo_tutor_clave');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_tutor');
    }
};
