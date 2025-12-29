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
        Schema::create('etiquetas_ensamblaje', function (Blueprint $table) {
            $table->id();

            // Código único de etiqueta (ej: ENS-P1-001-1/3)
            $table->string('codigo', 50)->unique();

            // Relaciones (tipos exactos para compatibilidad con tablas existentes)
            // planillas.id es bigint(20) sin unsigned
            // planilla_entidades.id es bigint(20) unsigned
            $table->bigInteger('planilla_id');
            $table->unsignedBigInteger('planilla_entidad_id');

            $table->foreign('planilla_id')->references('id')->on('planillas')->onDelete('cascade');
            $table->foreign('planilla_entidad_id')->references('id')->on('planilla_entidades')->onDelete('cascade');

            // Número de unidad (1, 2, 3... de la cantidad total)
            $table->unsignedSmallInteger('numero_unidad');
            $table->unsignedSmallInteger('total_unidades');

            // Estado del ensamblaje
            $table->enum('estado', ['pendiente', 'en_proceso', 'completada'])->default('pendiente');

            // Operarios asignados (opcional)
            $table->unsignedBigInteger('operario_id')->nullable();
            $table->foreign('operario_id')->references('id')->on('users')->nullOnDelete();

            // Fechas de proceso
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();

            // Datos copiados de la entidad para referencia rápida
            $table->string('marca', 50)->nullable();
            $table->string('situacion', 100)->nullable();
            $table->decimal('longitud', 8, 3)->nullable();
            $table->decimal('peso', 10, 3)->nullable();

            // Control de impresión
            $table->boolean('impresa')->default(false);
            $table->timestamp('fecha_impresion')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['planilla_id', 'estado']);
            $table->index('planilla_entidad_id');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etiquetas_ensamblaje');
    }
};
