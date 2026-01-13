<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Añade todas las columnas que faltan en etiquetas_ensamblaje.
     */
    public function up(): void
    {
        Schema::table('etiquetas_ensamblaje', function (Blueprint $table) {
            if (!Schema::hasColumn('etiquetas_ensamblaje', 'total_unidades')) {
                $table->unsignedSmallInteger('total_unidades')->after('numero_unidad');
            }
            if (!Schema::hasColumn('etiquetas_ensamblaje', 'marca')) {
                $table->string('marca', 50)->nullable()->after('estado');
            }
            if (!Schema::hasColumn('etiquetas_ensamblaje', 'situacion')) {
                $table->string('situacion', 100)->nullable()->after('marca');
            }
            if (!Schema::hasColumn('etiquetas_ensamblaje', 'longitud')) {
                $table->decimal('longitud', 8, 3)->nullable()->after('situacion');
            }
            if (!Schema::hasColumn('etiquetas_ensamblaje', 'peso')) {
                $table->decimal('peso', 10, 3)->nullable()->after('longitud');
            }
            if (!Schema::hasColumn('etiquetas_ensamblaje', 'operario_id')) {
                $table->unsignedBigInteger('operario_id')->nullable()->after('peso');
            }
            if (!Schema::hasColumn('etiquetas_ensamblaje', 'fecha_inicio')) {
                $table->timestamp('fecha_inicio')->nullable()->after('operario_id');
            }
            if (!Schema::hasColumn('etiquetas_ensamblaje', 'fecha_fin')) {
                $table->timestamp('fecha_fin')->nullable()->after('fecha_inicio');
            }
            if (!Schema::hasColumn('etiquetas_ensamblaje', 'impresa')) {
                $table->boolean('impresa')->default(false)->after('fecha_fin');
            }
            if (!Schema::hasColumn('etiquetas_ensamblaje', 'fecha_impresion')) {
                $table->timestamp('fecha_impresion')->nullable()->after('impresa');
            }
        });

        // Añadir foreign key de operario si no existe
        Schema::table('etiquetas_ensamblaje', function (Blueprint $table) {
            if (Schema::hasColumn('etiquetas_ensamblaje', 'operario_id')) {
                // Verificar si la FK ya existe antes de añadirla
                $foreignKeys = Schema::getConnection()
                    ->getDoctrineSchemaManager()
                    ->listTableForeignKeys('etiquetas_ensamblaje');

                $fkExists = collect($foreignKeys)->contains(function ($fk) {
                    return in_array('operario_id', $fk->getLocalColumns());
                });

                if (!$fkExists) {
                    $table->foreign('operario_id')->references('id')->on('users')->nullOnDelete();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etiquetas_ensamblaje', function (Blueprint $table) {
            $columns = ['total_unidades', 'marca', 'situacion', 'longitud', 'peso', 'operario_id', 'fecha_inicio', 'fecha_fin', 'impresa', 'fecha_impresion'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('etiquetas_ensamblaje', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
