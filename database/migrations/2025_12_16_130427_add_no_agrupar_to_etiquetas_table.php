<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('etiquetas', 'no_agrupar')) {
            Schema::table('etiquetas', function (Blueprint $table) {
                // Campo para marcar etiquetas desagrupadas manualmente que no deben reagruparse automáticamente
                $table->boolean('no_agrupar')->default(false)->after('grupo_resumen_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etiquetas', function (Blueprint $table) {
            $table->dropColumn('no_agrupar');
        });
    }
};
