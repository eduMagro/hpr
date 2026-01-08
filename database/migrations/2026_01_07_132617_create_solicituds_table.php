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
        Schema::create('solicituds', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->longText('descripcion')->nullable();
            $table->string('estado')->default('Nueva'); // Nueva, Lanzada, En progreso, En revisión, Merged, etc.
            $table->string('prioridad')->default('Media');
            $table->foreignId('user_id')->constrained('users'); // Creador
            $table->foreignId('asignado_a')->nullable()->constrained('users'); // Asignado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicituds');
    }
};
