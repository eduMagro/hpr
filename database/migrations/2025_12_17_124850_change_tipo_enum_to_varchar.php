<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cambia el ENUM a VARCHAR para permitir cualquier valor de tipo.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE incorporacion_formaciones MODIFY COLUMN tipo VARCHAR(100) NOT NULL");
    }

    public function down(): void
    {
        // No revertir - podría haber datos que no encajan en el ENUM original
    }
};
