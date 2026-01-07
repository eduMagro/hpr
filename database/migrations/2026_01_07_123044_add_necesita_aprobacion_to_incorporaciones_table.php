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
        Schema::table('incorporaciones', function (Blueprint $table) {
            // Por defecto true para mantener compatibilidad con incorporaciones existentes
            $table->boolean('necesita_aprobacion_rrhh')->default(true)->after('empresa_destino');
            $table->boolean('necesita_aprobacion_ceo')->default(true)->after('necesita_aprobacion_rrhh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incorporaciones', function (Blueprint $table) {
            $table->dropColumn(['necesita_aprobacion_rrhh', 'necesita_aprobacion_ceo']);
        });
    }
};
