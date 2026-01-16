<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Añade campos para turno partido (segunda jornada):
     * - entrada2: hora de entrada de la segunda jornada
     * - salida2: hora de salida de la segunda jornada
     */
    public function up(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            $table->time('entrada2')->nullable()->after('salida');
            $table->time('salida2')->nullable()->after('entrada2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            $table->dropColumn(['entrada2', 'salida2']);
        });
    }
};
