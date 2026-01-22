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
            // Eliminar índice si existe
            $table->dropIndex('fk_elementos_maquina3');

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
