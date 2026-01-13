<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Añade ferrawin_id para identificar elementos de forma única.
     * Formato: "{ZCODLIN}-{ZELEMENTO}" (ej: "001-01", "002-03")
     * Esto permite hacer upsert en lugar de delete+insert al resincronizar.
     */
    public function up(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->string('ferrawin_id', 50)->nullable()->after('planilla_id');

            // Índice compuesto para búsqueda rápida por planilla + ferrawin_id
            $table->index(['planilla_id', 'ferrawin_id'], 'idx_elementos_planilla_ferrawin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->dropIndex('idx_elementos_planilla_ferrawin');
            $table->dropColumn('ferrawin_id');
        });
    }
};
