<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Elimina la columna 'estado' de elementos ya que el estado
     * ahora se maneja a nivel de etiqueta (etiqueta.estado y etiqueta.estado2)
     */
    public function up(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->string('estado', 20)->default('pendiente')->after('tiempo_fabricacion');
        });
    }
};
