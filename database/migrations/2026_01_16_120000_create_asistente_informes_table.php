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
        Schema::create('asistente_informes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mensaje_id')->nullable()->constrained('chat_mensajes')->onDelete('set null');
            $table->string('tipo'); // stock_general, stock_critico, produccion_diaria, etc.
            $table->string('titulo');
            $table->json('parametros')->nullable(); // Filtros y parámetros usados
            $table->json('datos'); // Datos del informe
            $table->json('resumen')->nullable(); // Resumen ejecutivo
            $table->string('archivo_pdf')->nullable(); // Ruta al PDF generado
            $table->timestamp('expira_at'); // Fecha de expiración del informe
            $table->timestamps();

            $table->index(['user_id', 'tipo']);
            $table->index('expira_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistente_informes');
    }
};
