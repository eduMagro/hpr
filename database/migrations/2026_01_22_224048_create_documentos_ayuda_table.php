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
        Schema::create('documentos_ayuda', function (Blueprint $table) {
            $table->id();

            // Categorización
            $table->string('categoria', 50)->index();  // fichajes, vacaciones, pedidos...
            $table->string('titulo', 200);

            // Contenido (el "chunk" de texto)
            $table->text('contenido');

            // Embedding como JSON (array de 1536 floats para OpenAI)
            $table->json('embedding')->nullable();

            // Tags para búsqueda adicional
            $table->json('tags')->nullable();  // ["fichar", "entrada", "gps"]

            // Palabras clave para fallback sin IA
            $table->string('keywords', 500)->nullable();

            // Control
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);  // Para ordenar dentro de categoría

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Índices
            $table->index(['categoria', 'activo']);
            $table->fullText(['titulo', 'contenido', 'keywords']);  // Búsqueda fulltext MySQL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_ayuda');
    }
};
