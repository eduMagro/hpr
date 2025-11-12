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
        // Tabla de conversaciones
        Schema::create('chat_conversaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('titulo')->nullable();
            $table->timestamp('ultima_actividad')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'ultima_actividad']);
        });

        // Tabla de mensajes
        Schema::create('chat_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('chat_conversaciones')->onDelete('cascade');
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->text('contenido');
            $table->json('metadata')->nullable(); // Para guardar consultas SQL, resultados, etc.
            $table->timestamps();

            $table->index(['conversacion_id', 'created_at']);
        });

        // Tabla de consultas ejecutadas (para auditoría)
        Schema::create('chat_consultas_sql', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensaje_id')->constrained('chat_mensajes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('consulta_sql');
            $table->text('consulta_natural'); // La pregunta del usuario
            $table->json('resultados')->nullable();
            $table->integer('filas_afectadas')->default(0);
            $table->boolean('exitosa')->default(true);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_consultas_sql');
        Schema::dropIfExists('chat_mensajes');
        Schema::dropIfExists('chat_conversaciones');
    }
};
