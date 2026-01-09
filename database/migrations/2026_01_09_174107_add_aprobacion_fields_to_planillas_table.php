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
        Schema::table('planillas', function (Blueprint $table) {
            // Fecha de creación en Ferrawin (ZFECHA de ORD_HEAD)
            $table->datetime('fecha_creacion_ferrawin')->nullable()->after('codigo');

            // Sistema de aprobación por técnico de despiece
            $table->boolean('aprobada')->default(false)->after('revisada_at');
            $table->foreignId('aprobada_por_id')->nullable()->after('aprobada')
                  ->constrained('users')->nullOnDelete();
            $table->datetime('aprobada_at')->nullable()->after('aprobada_por_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planillas', function (Blueprint $table) {
            $table->dropForeign(['aprobada_por_id']);
            $table->dropColumn([
                'fecha_creacion_ferrawin',
                'aprobada',
                'aprobada_por_id',
                'aprobada_at',
            ]);
        });
    }
};
