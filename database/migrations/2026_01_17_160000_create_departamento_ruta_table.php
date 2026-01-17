<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departamento_ruta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->onDelete('cascade');
            $table->string('ruta'); // Ej: "vacaciones.eliminarSolicitud" o "usuarios.*" para todas
            $table->string('descripcion')->nullable(); // Descripción legible
            $table->timestamps();

            $table->unique(['departamento_id', 'ruta']);
            $table->index('departamento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departamento_ruta');
    }
};
