<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Añade campo para guardar el ID del producto que fue consumido automáticamente
     * cuando se movió un nuevo producto a la máquina
     */
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('producto_consumido_id')->nullable()->after('producto_id');
            $table->foreign('producto_consumido_id')->references('id')->on('productos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropForeign(['producto_consumido_id']);
            $table->dropColumn('producto_consumido_id');
        });
    }
};
