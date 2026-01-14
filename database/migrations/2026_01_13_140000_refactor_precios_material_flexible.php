<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Refactoriza las tablas de precios de material para mayor flexibilidad.
     *
     * Cambios:
     * - Formatos: Una sola columna de incremento (base/defecto)
     * - Excepciones: distribuidor_id opcional (null = aplica a todos los distribuidores)
     */
    public function up(): void
    {
        // 1. Modificar tabla formatos: unificar incrementos en uno solo
        Schema::table('precios_material_formatos', function (Blueprint $table) {
            $table->renameColumn('incremento_general', 'incremento');
        });

        Schema::table('precios_material_formatos', function (Blueprint $table) {
            $table->dropColumn('incremento_siderurgica');
        });

        // 2. Modificar excepciones: hacer distribuidor_id nullable
        // Primero eliminar todas las foreign keys y el índice único
        Schema::table('precios_material_excepciones', function (Blueprint $table) {
            $table->dropForeign(['distribuidor_id']);
            $table->dropForeign(['fabricante_id']);
        });

        // Eliminar el índice único
        Schema::table('precios_material_excepciones', function (Blueprint $table) {
            $table->dropUnique('excepcion_unica');
        });

        // Hacer distribuidor_id nullable
        Schema::table('precios_material_excepciones', function (Blueprint $table) {
            $table->unsignedBigInteger('distribuidor_id')->nullable()->change();
        });

        // Volver a añadir las foreign keys
        Schema::table('precios_material_excepciones', function (Blueprint $table) {
            $table->foreign('distribuidor_id')->references('id')->on('distribuidores')->nullOnDelete();
            $table->foreign('fabricante_id')->references('id')->on('fabricantes')->cascadeOnDelete();
        });

        // Crear nuevo índice único
        // Nota: En MySQL/MariaDB, los valores NULL se consideran únicos entre sí
        Schema::table('precios_material_excepciones', function (Blueprint $table) {
            $table->unique(['distribuidor_id', 'fabricante_id', 'formato_codigo'], 'excepcion_unica');
        });

        // 3. Migrar datos de Siderúrgica Sevillana a excepciones
        // Buscar el fabricante Siderúrgica Sevillana
        $siderurgica = DB::table('fabricantes')
            ->whereRaw('LOWER(nombre) LIKE ?', ['%siderurgica%'])
            ->first();

        if ($siderurgica) {
            // Insertar excepción para encarretado de Siderúrgica (20€ en vez de 30€)
            DB::table('precios_material_excepciones')->insertOrIgnore([
                'distribuidor_id' => null,
                'fabricante_id' => $siderurgica->id,
                'formato_codigo' => 'encarretado',
                'incremento' => 20.00,
                'notas' => 'Precio especial Siderúrgica Sevillana para encarretado',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar columnas en formatos
        Schema::table('precios_material_formatos', function (Blueprint $table) {
            $table->renameColumn('incremento', 'incremento_general');
        });

        Schema::table('precios_material_formatos', function (Blueprint $table) {
            $table->decimal('incremento_siderurgica', 10, 2)->default(0)->after('incremento_general');
        });

        // Restaurar distribuidor_id como required
        Schema::table('precios_material_excepciones', function (Blueprint $table) {
            $table->dropForeign(['distribuidor_id']);
        });

        // Eliminar excepciones sin distribuidor
        DB::table('precios_material_excepciones')->whereNull('distribuidor_id')->delete();

        Schema::table('precios_material_excepciones', function (Blueprint $table) {
            $table->unsignedBigInteger('distribuidor_id')->nullable(false)->change();
            $table->foreign('distribuidor_id')->references('id')->on('distribuidores')->cascadeOnDelete();
        });
    }
};
