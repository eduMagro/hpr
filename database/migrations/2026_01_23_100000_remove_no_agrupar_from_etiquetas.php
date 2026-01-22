<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('etiquetas', function (Blueprint $table) {
            $table->dropColumn('no_agrupar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etiquetas', function (Blueprint $table) {
            $table->boolean('no_agrupar')->default(false)->after('grupo_resumen_id');
        });
    }
};
