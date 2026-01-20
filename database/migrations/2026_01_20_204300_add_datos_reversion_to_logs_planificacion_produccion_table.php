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
        Schema::table('logs_planificacion_produccion', function (Blueprint $table) {
            $table->json('datos_reversion')->nullable()->after('detalles');
            $table->boolean('revertido')->default(false)->after('datos_reversion');
            $table->timestamp('revertido_at')->nullable()->after('revertido');
            $table->foreignId('revertido_por')->nullable()->after('revertido_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs_planificacion_produccion', function (Blueprint $table) {
            $table->dropForeign(['revertido_por']);
            $table->dropColumn(['datos_reversion', 'revertido', 'revertido_at', 'revertido_por']);
        });
    }
};
