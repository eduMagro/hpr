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
        // Verificar si la columna existe antes de intentar eliminarla
        if (!Schema::hasColumn('elementos', 'maquina_id_3')) {
            return;
        }

        // Obtener el nombre real de la FK desde la BD
        $fkName = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'elementos'
            AND COLUMN_NAME = 'maquina_id_3'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        Schema::table('elementos', function (Blueprint $table) use ($fkName) {
            // Eliminar FK si existe (con el nombre real)
            if ($fkName) {
                $table->dropForeign($fkName->CONSTRAINT_NAME);
            }

            // Eliminar la columna
            $table->dropColumn('maquina_id_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->unsignedBigInteger('maquina_id_3')->nullable()->after('maquina_id_2');

            $table->foreign('maquina_id_3')
                ->references('id')
                ->on('maquinas')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }
};
