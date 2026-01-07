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
        Schema::table('planilla_entidades', function (Blueprint $table) {
            // Datos de dibujo extraídos de ZOBJETO de FerraWin
            // Contiene coordenadas y parámetros para representación gráfica
            $table->json('dibujo_data')->nullable()->after('composicion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planilla_entidades', function (Blueprint $table) {
            $table->dropColumn('dibujo_data');
        });
    }
};
