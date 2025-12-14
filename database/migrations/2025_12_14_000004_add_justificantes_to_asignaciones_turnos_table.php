<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('asignaciones_turnos')) {
            return;
        }

        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            if (!Schema::hasColumn('asignaciones_turnos', 'justificante_ruta')) {
                $table->string('justificante_ruta')->nullable()->after('fecha');
                $table->index('justificante_ruta');
            }

            if (!Schema::hasColumn('asignaciones_turnos', 'horas_justificadas')) {
                $table->decimal('horas_justificadas', 5, 2)->nullable()->after('justificante_ruta');
            }

            if (!Schema::hasColumn('asignaciones_turnos', 'justificante_observaciones')) {
                $table->text('justificante_observaciones')->nullable()->after('horas_justificadas');
            }

            if (!Schema::hasColumn('asignaciones_turnos', 'justificante_subido_at')) {
                $table->timestamp('justificante_subido_at')->nullable()->after('justificante_observaciones');
                $table->index('justificante_subido_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('asignaciones_turnos')) {
            return;
        }

        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            if (Schema::hasColumn('asignaciones_turnos', 'justificante_subido_at')) {
                $table->dropIndex(['justificante_subido_at']);
                $table->dropColumn('justificante_subido_at');
            }

            if (Schema::hasColumn('asignaciones_turnos', 'justificante_observaciones')) {
                $table->dropColumn('justificante_observaciones');
            }

            if (Schema::hasColumn('asignaciones_turnos', 'horas_justificadas')) {
                $table->dropColumn('horas_justificadas');
            }

            if (Schema::hasColumn('asignaciones_turnos', 'justificante_ruta')) {
                $table->dropIndex(['justificante_ruta']);
                $table->dropColumn('justificante_ruta');
            }
        });
    }
};

