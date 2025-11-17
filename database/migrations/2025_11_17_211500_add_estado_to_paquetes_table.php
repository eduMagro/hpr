<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
    }

    public function down(): void
    {
        Schema::table('paquetes', function (Blueprint $table) {
            if (Schema::hasColumn('paquetes', 'estado')) {
                $table->dropColumn('estado');
            }
        });
    }
};
