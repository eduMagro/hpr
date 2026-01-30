<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE paquetes MODIFY COLUMN estado ENUM('pendiente', 'asignado_a_salida', 'en_reparto', 'enviado', 'entregado') DEFAULT 'pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Primero actualizar los registros con 'entregado' a 'enviado'
        DB::table('paquetes')->where('estado', 'entregado')->update(['estado' => 'enviado']);

        DB::statement("ALTER TABLE paquetes MODIFY COLUMN estado ENUM('pendiente', 'asignado_a_salida', 'en_reparto', 'enviado') DEFAULT 'pendiente'");
    }
};
