<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identificador_fotos', function (Blueprint $table) {
            $table->increments('id_unico');
            $table->string('curp', 18)->nullable();
            $table->string('hash_foto', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identificador_fotos');
    }
};
