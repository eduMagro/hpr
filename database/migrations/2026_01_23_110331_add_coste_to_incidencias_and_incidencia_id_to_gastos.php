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
        Schema::table('incidencias', function (Blueprint $table) {
            $table->decimal('coste', 10, 2)->nullable()->after('resolucion');
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->foreignId('incidencia_id')->nullable()->after('observaciones')->constrained('incidencias')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropForeign(['incidencia_id']);
            $table->dropColumn('incidencia_id');
        });

        Schema::table('incidencias', function (Blueprint $table) {
            $table->dropColumn('coste');
        });
    }
};
