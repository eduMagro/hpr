<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            $table->unsignedSmallInteger('anio_cargo')->nullable()->after('estado')
                ->comment('Año al que se cargan las vacaciones (permite usar días del año anterior)');
        });
    }

    public function down(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            $table->dropColumn('anio_cargo');
        });
    }
};
