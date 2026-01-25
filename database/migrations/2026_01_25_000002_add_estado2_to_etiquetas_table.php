<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('etiquetas', function (Blueprint $table) {
            $table->string('estado2', 30)->nullable()->after('estado');
            $table->index('estado2', 'idx_etiquetas_estado2');
        });

        // Migrar datos existentes: etiquetas con elementos que tienen maquina_id_2
        // Se establece estado2='pendiente' para que aparezcan en la cola de la dobladora
        DB::statement("
            UPDATE etiquetas e
            SET e.estado2 = 'pendiente'
            WHERE e.estado2 IS NULL
            AND EXISTS (
                SELECT 1 FROM elementos el
                WHERE el.etiqueta_sub_id = e.etiqueta_sub_id
                AND el.maquina_id_2 IS NOT NULL
                AND el.deleted_at IS NULL
            )
            AND e.deleted_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etiquetas', function (Blueprint $table) {
            $table->dropIndex('idx_etiquetas_estado2');
            $table->dropColumn('estado2');
        });
    }
};
