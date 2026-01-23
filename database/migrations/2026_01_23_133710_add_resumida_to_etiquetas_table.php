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
        Schema::table('etiquetas', function (Blueprint $table) {
            $table->boolean('resumida')->default(false)->after('grupo_resumen_id')
                ->comment('Indica si ya fue procesada por el resumen automático');
            $table->index('resumida');
        });
    }

    public function down(): void
    {
        Schema::table('etiquetas', function (Blueprint $table) {
            $table->dropIndex(['resumida']);
            $table->dropColumn('resumida');
        });
    }
};
