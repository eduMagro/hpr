<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ia_aprendizaje_prioridad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrada_import_log_id')->constrained('entrada_import_logs')->onDelete('cascade');
            $table->json('payload_ocr')->comment('El JSON extraído por Docupipe');
            $table->json('recomendaciones_ia')->comment('Los pedidos sugeridos por la IA y su ranking');
            $table->integer('pedido_seleccionado_id')->nullable()->comment('El ID del pedido que eligió el usuario');
            $table->boolean('es_discrepancia')->default(false)->comment('¿El usuario eligió algo distinto al #1?');
            $table->text('motivo_usuario')->nullable()->comment('Explicación del humano sobre la decisión');
            $table->json('contexto_sistema')->nullable()->comment('Datos adicionales usados para la decisión (stock, etc)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ia_aprendizaje_prioridad');
    }
};
