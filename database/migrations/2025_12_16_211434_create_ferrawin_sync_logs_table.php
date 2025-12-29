<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ferrawin_sync_logs')) {
            Schema::create('ferrawin_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->timestamp('fecha_ejecucion');
                $table->string('estado', 50)->default('pendiente');
                $table->unsignedInteger('planillas_encontradas')->default(0);
                $table->unsignedInteger('planillas_nuevas')->default(0);
                $table->unsignedInteger('planillas_actualizadas')->default(0);
                $table->unsignedInteger('planillas_sincronizadas')->default(0);
                $table->unsignedInteger('planillas_fallidas')->default(0);
                $table->unsignedInteger('elementos_creados')->default(0);
                $table->json('errores')->nullable();
                $table->json('advertencias')->nullable();
                $table->decimal('duracion_segundos', 10, 2)->default(0);
                $table->timestamps();

                $table->index('fecha_ejecucion');
                $table->index('estado');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ferrawin_sync_logs');
    }
};
