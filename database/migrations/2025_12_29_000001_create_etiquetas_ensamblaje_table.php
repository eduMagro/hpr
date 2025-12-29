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
            $table->string('codigo', 20)->unique()->comment('Código único ETA + YYMM + secuencial');

            // Relaciones
            $table->foreignId('planilla_id')->constrained('planillas')->onDelete('cascade');
            $table->foreignId('planilla_entidad_id')->constrained('planilla_entidades')->onDelete('cascade');
            $table->unsignedInteger('numero_unidad')->default(1)->comment('Unidad X de N (para entidades con cantidad > 1)');

            // Personal asignado
            $table->foreignId('ensamblador1_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('ensamblador2_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('maquina_id')->nullable()->constrained('maquinas')->onDelete('set null')->comment('Mesa de ensamblaje');

            // Estado del ensamblaje
            $table->enum('estado', ['pendiente', 'en_preparacion', 'ensamblando', 'completada'])->default('pendiente');
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_finalizacion')->nullable();

            // Datos calculados
            $table->decimal('peso_total', 10, 2)->nullable()->comment('Peso total en kg');
            $table->decimal('longitud_total', 10, 2)->nullable()->comment('Longitud total en metros');

            // Control de impresión
            $table->boolean('impresa')->default(false);
            $table->timestamp('fecha_impresion')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['planilla_id', 'estado']);
            $table->index('codigo');
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
