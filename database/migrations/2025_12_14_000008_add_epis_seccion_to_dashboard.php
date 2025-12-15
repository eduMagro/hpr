<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('secciones')) {
            return;
        }

        $exists = DB::table('secciones')
            ->where('ruta', 'epis.index')
            ->exists();

        if ($exists) {
            DB::table('secciones')
                ->where('ruta', 'epis.index')
                ->update([
                    'nombre' => 'EPIs',
                    'mostrar_en_dashboard' => 1,
                ]);
            return;
        }

        DB::table('secciones')->insert([
            'nombre' => 'EPIs',
            'ruta' => 'epis.index',
            'icono' => null,
            'mostrar_en_dashboard' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('secciones')) {
            return;
        }

        DB::table('secciones')
            ->where('ruta', 'epis.index')
            ->delete();
    }
};

