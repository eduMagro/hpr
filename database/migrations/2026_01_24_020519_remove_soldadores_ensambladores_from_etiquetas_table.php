<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Intentar eliminar foreign keys si existen
        $foreignKeys = [
            'etiquetas_soldador1_id_foreign',
            'etiquetas_soldador2_id_foreign',
            'etiquetas_ensamblador1_id_foreign',
            'etiquetas_ensamblador2_id_foreign',
        ];

        foreach ($foreignKeys as $fk) {
            try {
                Schema::table('etiquetas', function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk);
                });
            } catch (\Exception $e) {
                // La FK no existe, continuar
            }
        }

        // Eliminar columnas
        Schema::table('etiquetas', function (Blueprint $table) {
            $columns = ['soldador1_id', 'soldador2_id', 'ensamblador1_id', 'ensamblador2_id'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('etiquetas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etiquetas', function (Blueprint $table) {
            if (!Schema::hasColumn('etiquetas', 'soldador1_id')) {
                $table->foreignId('soldador1_id')->nullable()->after('operario2_id');
            }
            if (!Schema::hasColumn('etiquetas', 'soldador2_id')) {
                $table->foreignId('soldador2_id')->nullable()->after('soldador1_id');
            }
            if (!Schema::hasColumn('etiquetas', 'ensamblador1_id')) {
                $table->foreignId('ensamblador1_id')->nullable()->after('soldador2_id');
            }
            if (!Schema::hasColumn('etiquetas', 'ensamblador2_id')) {
                $table->foreignId('ensamblador2_id')->nullable()->after('ensamblador1_id');
            }
        });
    }
};
