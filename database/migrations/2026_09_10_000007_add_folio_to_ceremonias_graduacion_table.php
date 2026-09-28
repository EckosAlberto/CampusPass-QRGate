<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ceremonias_graduacion', function (Blueprint $table) {
            $table->unsignedInteger('folio')->nullable()->unique()->after('id');
        });

        DB::table('ceremonias_graduacion')
            ->orderBy('created_at')
            ->pluck('id')
            ->each(function (string $id, int $indice) {
                DB::table('ceremonias_graduacion')->where('id', $id)->update(['folio' => $indice + 1]);
            });
    }

    public function down(): void
    {
        Schema::table('ceremonias_graduacion', function (Blueprint $table) {
            $table->dropColumn('folio');
        });
    }
};
