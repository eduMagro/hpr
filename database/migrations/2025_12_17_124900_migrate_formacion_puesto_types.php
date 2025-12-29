<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migra los tipos antiguos de formación al nuevo tipo unificado.
     * - formacion_generica_puesto -> formacion_puesto
     * - formacion_especifica_puesto -> formacion_puesto
     */
    public function up(): void
    {
        // Migrar en tabla incorporacion_documentos
        DB::table('incorporacion_documentos')
            ->whereIn('tipo', ['formacion_generica_puesto', 'formacion_especifica_puesto'])
            ->update(['tipo' => 'formacion_puesto']);

        // Migrar en tabla incorporacion_formaciones
        DB::table('incorporacion_formaciones')
            ->whereIn('tipo', ['formacion_generica_puesto', 'formacion_especifica_puesto'])
            ->update(['tipo' => 'formacion_puesto']);
    }

    /**
     * No se puede revertir porque no sabemos cuál era el tipo original.
     */
    public function down(): void
    {
        // No reversible - los datos originales se perderían
    }
};
