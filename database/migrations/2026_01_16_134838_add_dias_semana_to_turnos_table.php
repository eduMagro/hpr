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
        Schema::table('turnos', function (Blueprint $table) {
            // JSON con los días que trabaja este turno
            // null = default (lunes a viernes)
            // Ejemplo: ["lunes", "martes", "miercoles", "jueves", "viernes", "sabado"]
            $table->json('dias_semana')->nullable()->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn(['dias_semana']);
        });
    }
};
