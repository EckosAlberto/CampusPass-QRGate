<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evento_tutorias', function (Blueprint $table) {
            $table->timestamp('entrada_generada_en')->nullable();
            $table->timestamp('salida_generada_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('evento_tutorias', function (Blueprint $table) {
            $table->dropColumn(['entrada_generada_en', 'salida_generada_en']);
        });
    }
};
