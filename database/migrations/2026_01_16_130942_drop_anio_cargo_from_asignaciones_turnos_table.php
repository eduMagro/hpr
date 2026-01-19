<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('asignaciones_turnos', function (Blueprint $table) {
                $table->dropColumn('anio_cargo');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Code 42S22: Column not found in MySQL
            if ($e->getCode() != '42S22') {
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            $table->year('anio_cargo')->nullable()->after('fecha');
        });
    }
};
