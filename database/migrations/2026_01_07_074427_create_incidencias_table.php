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
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquina_id')->constrained('maquinas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Reported by
            $table->string('titulo')->nullable();
            $table->text('descripcion');
            $table->json('fotos')->nullable(); // Store paths as JSON array
            $table->string('estado')->default('abierta'); // abierta, en_proceso, resuelta
            $table->string('prioridad')->default('media'); // baja, media, alta, critica
            $table->timestamp('fecha_reporte')->useCurrent();
            $table->timestamp('fecha_resolucion')->nullable();
            $table->text('resolucion')->nullable();
            $table->foreignId('resuelto_por')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};
