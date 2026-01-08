<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero, mapear los valores enum existentes a IDs de empresa
        // hierros_paco_reyes -> 1, hpr_servicios -> 2
        DB::statement("UPDATE incorporaciones SET empresa_destino = '1' WHERE empresa_destino = 'hierros_paco_reyes'");
        DB::statement("UPDATE incorporaciones SET empresa_destino = '2' WHERE empresa_destino = 'hpr_servicios'");

        // Cambiar la columna de ENUM a BIGINT UNSIGNED
        Schema::table('incorporaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_destino')->nullable()->change();
        });

        // Agregar foreign key
        Schema::table('incorporaciones', function (Blueprint $table) {
            $table->foreign('empresa_destino')->references('id')->on('empresas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Quitar foreign key
        Schema::table('incorporaciones', function (Blueprint $table) {
            $table->dropForeign(['empresa_destino']);
        });

        // Volver a ENUM
        Schema::table('incorporaciones', function (Blueprint $table) {
            $table->enum('empresa_destino', ['hpr_servicios', 'hierros_paco_reyes'])->nullable()->change();
        });

        // Mapear IDs de vuelta a valores enum
        DB::statement("UPDATE incorporaciones SET empresa_destino = 'hierros_paco_reyes' WHERE empresa_destino = '1'");
        DB::statement("UPDATE incorporaciones SET empresa_destino = 'hpr_servicios' WHERE empresa_destino = '2'");
    }
};
