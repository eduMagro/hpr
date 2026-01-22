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
        Schema::create('logs_planificacion_salidas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('accion', 50);
            $table->string('descripcion', 500);
            $table->json('detalles')->nullable();
            $table->json('datos_reversion')->nullable();
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->unsignedBigInteger('salida_id')->nullable();
            $table->boolean('revertido')->default(false);
            $table->timestamp('revertido_at')->nullable();
            $table->unsignedBigInteger('revertido_por')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['accion']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs_planificacion_salidas');
    }
};
