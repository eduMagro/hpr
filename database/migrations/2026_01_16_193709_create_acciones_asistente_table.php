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
        Schema::create('acciones_asistente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tipo', 50)->index(); // Tipo de herramienta/acción
            $table->json('parametros')->nullable(); // Parámetros de la acción
            $table->json('resultado')->nullable(); // Resultado de la ejecución
            $table->boolean('exito')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index(['user_id', 'created_at']);
            $table->index(['tipo', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acciones_asistente');
    }
};
