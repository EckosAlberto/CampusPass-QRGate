<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->integer('capacidad')->nullable();
            $table->boolean('estatus')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};
