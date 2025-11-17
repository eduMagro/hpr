<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paquetes', function (Blueprint $table) {
            if (!Schema::hasColumn('paquetes', 'estado')) {
                $table->enum('estado', [
                    'pendiente',
                    'asignado_a_salida',
                    'en_reparto',
                    'enviado',
                ])->default('pendiente')->after('peso');
            }
        });

        if (Schema::hasColumn('paquetes', 'estado')) {
            DB::statement("
                ALTER TABLE `paquetes`
                MODIFY `estado` ENUM('pendiente','asignado_a_salida','en_reparto','enviado')
                NOT NULL DEFAULT 'pendiente'
            ");
        }
    }

    public function down(): void
    {
        // No revert exact previous type because era desconocido; mantener columna existente.
    }
};
