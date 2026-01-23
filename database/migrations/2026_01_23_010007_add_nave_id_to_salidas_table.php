<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Añade nave_id para separar salidas por nave (Nave A = 1, Nave B = 2)
     * Las planillas con ensamblado='taller' van a Nave B, el resto a Nave A
     */
    public function up(): void
    {
        Schema::table('salidas', function (Blueprint $table) {
            $table->unsignedBigInteger('nave_id')->nullable()->after('obra_id');
            $table->foreign('nave_id')->references('id')->on('obras')->nullOnDelete();

            // Índice compuesto para búsquedas de salidas por obra+fecha+nave
            $table->index(['obra_id', 'fecha_salida', 'nave_id'], 'salidas_obra_fecha_nave_idx');
        });

        // Establecer nave_id = 1 (Nave A) para salidas existentes sin nave
        \DB::table('salidas')->whereNull('nave_id')->update(['nave_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salidas', function (Blueprint $table) {
            $table->dropIndex('salidas_obra_fecha_nave_idx');
            $table->dropForeign(['nave_id']);
            $table->dropColumn('nave_id');
        });
    }
};
