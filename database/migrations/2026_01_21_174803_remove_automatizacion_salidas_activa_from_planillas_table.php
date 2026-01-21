<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Elimina el campo automatizacion_salidas_activa ya que la automatización
     * ahora se basa en la existencia de salidas con obra_id + fecha_salida.
     */
    public function up(): void
    {
        Schema::table('planillas', function (Blueprint $table) {
            $table->dropColumn('automatizacion_salidas_activa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planillas', function (Blueprint $table) {
            $table->boolean('automatizacion_salidas_activa')->default(false)->after('estado');
        });
    }
};
