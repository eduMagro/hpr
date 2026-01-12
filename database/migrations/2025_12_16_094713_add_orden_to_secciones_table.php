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
        if (!Schema::hasTable('secciones')) {
            return;
        }

        Schema::table('secciones', function (Blueprint $table) {
            if (Schema::hasColumn('secciones', 'orden')) {
                return;
            }

            $table->unsignedInteger('orden')->default(0)->after('mostrar_en_dashboard');
        });

        // Asignar orden inicial basado en el ID
        DB::statement('UPDATE secciones SET orden = id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('secciones')) {
            return;
        }

        Schema::table('secciones', function (Blueprint $table) {
            if (!Schema::hasColumn('secciones', 'orden')) {
                return;
            }

            $table->dropColumn('orden');
        });
    }
};
