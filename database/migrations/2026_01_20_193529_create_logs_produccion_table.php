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
        Schema::create('logs_planificacion_produccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('accion', 50); // optimizar, balancear, priorizar, mover_elemento, cambiar_posicion, deshacer
            $table->string('descripcion'); // Descripción legible de la acción
            $table->json('detalles')->nullable(); // Datos adicionales (elementos movidos, posiciones, etc.)
            $table->unsignedBigInteger('maquina_id')->nullable();
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->unsignedBigInteger('elemento_id')->nullable();
            $table->timestamps();

            $table->index('accion');
            $table->index('created_at');
            $table->index(['maquina_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs_planificacion_produccion');
    }
};
