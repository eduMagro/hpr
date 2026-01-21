<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Añade obra_id a salidas para identificar la obra prioritaria de la salida.
     * Esto permite vincular automáticamente paquetes a salidas por obra + fecha.
     */
    public function up(): void
    {
        Schema::table('salidas', function (Blueprint $table) {
            $table->foreignId('obra_id')->nullable()->after('id')->constrained('obras')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salidas', function (Blueprint $table) {
            $table->dropForeign(['obra_id']);
            $table->dropColumn('obra_id');
        });
    }
};
