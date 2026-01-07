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
        Schema::table('elementos', function (Blueprint $table) {
            $table->foreignId('planilla_entidad_id')
                ->nullable()
                ->after('planilla_id')
                ->constrained('planilla_entidades')
                ->nullOnDelete();

            $table->foreignId('etiqueta_ensamblaje_id')
                ->nullable()
                ->after('planilla_entidad_id')
                ->constrained('etiquetas_ensamblaje')
                ->nullOnDelete();

            // Índice para búsquedas
            $table->index(['planilla_id', 'planilla_entidad_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->dropIndex(['planilla_id', 'planilla_entidad_id']);
            $table->dropForeign(['etiqueta_ensamblaje_id']);
            $table->dropForeign(['planilla_entidad_id']);
            $table->dropColumn(['etiqueta_ensamblaje_id', 'planilla_entidad_id']);
        });
    }
};
