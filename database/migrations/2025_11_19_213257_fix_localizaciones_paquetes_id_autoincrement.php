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
        // Arreglar la columna 'id' para que sea AUTO_INCREMENT (sin redefinir la PK si ya existe)
        DB::statement('ALTER TABLE localizaciones_paquetes MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No es necesario revertir, pero por completitud:
        DB::statement('ALTER TABLE localizaciones_paquetes MODIFY COLUMN id BIGINT UNSIGNED NOT NULL');
    }
};
