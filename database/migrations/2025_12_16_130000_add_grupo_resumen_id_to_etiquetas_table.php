<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('etiquetas', function (Blueprint $table) {
            if (!Schema::hasColumn('etiquetas', 'grupo_resumen_id')) {
                $table->unsignedBigInteger('grupo_resumen_id')->nullable()->after('estado');
                $table->index('grupo_resumen_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etiquetas', function (Blueprint $table) {
            $table->dropColumn('grupo_resumen_id');
        });
    }
};
