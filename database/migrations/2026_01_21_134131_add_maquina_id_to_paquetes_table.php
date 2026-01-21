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
        Schema::table('paquetes', function (Blueprint $table) {
            $table->unsignedBigInteger('maquina_id')->nullable()->after('ubicacion_id');

            $table->foreign('maquina_id')
                ->references('id')
                ->on('maquinas')
                ->onDelete('set null');

            $table->index('maquina_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paquetes', function (Blueprint $table) {
            $table->dropForeign(['maquina_id']);
            $table->dropIndex(['maquina_id']);
            $table->dropColumn('maquina_id');
        });
    }
};
