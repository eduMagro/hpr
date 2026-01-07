<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla para gestionar el orden de trabajo de ensamblaje.
     *
     * A diferencia de orden_planillas (que ordena planillas completas),
     * esta tabla ordena entidades individuales (pilares, vigas, etc.)
     * y solo se activan cuando todos sus elementos están fabricados.
     */
    public function up(): void
    {
        Schema::create('orden_planillas_ensamblaje', function (Blueprint $table) {
            $table->id();

            // Máquina ensambladora
            $table->foreignId('maquina_id')
                ->constrained('maquinas')
                ->cascadeOnDelete();

            // Entidad a ensamblar (pilar, viga, etc.)
            $table->foreignId('planilla_entidad_id')
                ->constrained('planilla_entidades')
                ->cascadeOnDelete();

            // Posición en la cola de trabajo (1, 2, 3...)
            $table->unsignedInteger('posicion')->default(0);

            // Estado de la orden
            $table->enum('estado', ['pendiente', 'en_proceso', 'completada', 'pausada'])
                ->default('pendiente');

            // Prioridad (1 = máxima, 5 = mínima)
            $table->unsignedTinyInteger('prioridad')->default(3);

            // Notas del planificador
            $table->text('notas')->nullable();

            // Usuario que asignó la orden
            $table->foreignId('asignado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Fechas
            $table->timestamp('fecha_asignacion')->nullable();
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['maquina_id', 'posicion']);
            $table->index(['maquina_id', 'estado']);

            // Una entidad solo puede estar una vez en la cola de una máquina
            $table->unique(['maquina_id', 'planilla_entidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_planillas_ensamblaje');
    }
};
