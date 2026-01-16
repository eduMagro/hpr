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
            $table->foreignId('conversacion_id')->nullable()->constrained('chat_conversaciones')->onDelete('set null');
            $table->string('accion'); // Tipo de acción ejecutada
            $table->json('parametros'); // Parámetros usados
            $table->json('resultado'); // Resultado de la ejecución
            $table->string('ip', 45)->nullable(); // IPv4 o IPv6
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index(['user_id', 'created_at']);
            $table->index('accion');
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
