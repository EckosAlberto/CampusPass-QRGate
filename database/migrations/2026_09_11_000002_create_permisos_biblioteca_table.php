<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos_biblioteca', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('etiqueta', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos_biblioteca');
    }
};
