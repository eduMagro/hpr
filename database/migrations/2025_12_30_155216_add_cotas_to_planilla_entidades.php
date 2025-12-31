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
            // Campo para almacenar las cotas/dimensiones visuales del ensamblaje
            // Ejemplo: "40 |______224______| 40" o "ESTRIBO 20 x 20 Solape= 10"
            $table->string('cotas', 255)->nullable()->after('modelo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planilla_entidades', function (Blueprint $table) {
            $table->dropColumn('cotas');
        });
    }
};
