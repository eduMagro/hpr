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
        // Tabla para almacenar resúmenes de conversaciones largas
        // Reduce tokens enviados a IA resumiendo mensajes antiguos
        Schema::create('chat_resumen_contexto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('chat_conversaciones')->onDelete('cascade');
            $table->text('resumen'); // Resumen generado por IA
            $table->integer('mensajes_desde')->default(1); // Desde qué mensaje
            $table->integer('mensajes_hasta'); // Hasta qué mensaje
            $table->integer('tokens_original')->default(0); // Tokens antes de resumir
            $table->integer('tokens_resumen')->default(0); // Tokens del resumen
            $table->timestamps();

            $table->index(['conversacion_id', 'mensajes_hasta']);
        });

        // Tabla para memoria de sesión - información clave de la conversación
        // Guarda decisiones, preferencias, y datos importantes mencionados
        Schema::create('chat_memoria_sesion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('chat_conversaciones')->onDelete('cascade');
            $table->string('tipo', 50); // decision, preferencia, dato_clave, conclusion
            $table->string('clave', 100); // identificador de la memoria
            $table->text('valor'); // contenido de la memoria
            $table->float('confianza')->default(0.8); // 0-1 confianza de la memoria
            $table->integer('veces_referenciado')->default(0); // cuántas veces se usó
            $table->timestamps();

            $table->index(['conversacion_id', 'tipo']);
            $table->index(['conversacion_id', 'clave']);
        });

        // Tabla para rastreo de entidades mencionadas en la conversación
        // Permite resolver referencias como "esa planilla", "la otra máquina"
        Schema::create('chat_estado_entidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('chat_conversaciones')->onDelete('cascade');
            $table->string('tipo_entidad', 50); // planilla, maquina, cliente, obra, pedido, usuario
            $table->unsignedBigInteger('entidad_id'); // ID de la entidad referenciada
            $table->string('referencia', 255)->nullable(); // cómo la mencionó el usuario (ej: "P-1234", "la Syntax")
            $table->integer('orden_mencion')->default(1); // orden en que se mencionó (1=más reciente)
            $table->json('contexto')->nullable(); // datos adicionales de la entidad en ese momento
            $table->timestamp('ultima_mencion')->useCurrent();
            $table->timestamps();

            $table->index(['conversacion_id', 'tipo_entidad', 'orden_mencion'], 'chat_entidades_conv_tipo_orden_idx');
            $table->index(['conversacion_id', 'ultima_mencion'], 'chat_entidades_conv_mencion_idx');
        });

        // Tabla para preferencias de usuario persistentes entre conversaciones
        Schema::create('chat_preferencias_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('clave', 100); // ej: formato_respuesta, nivel_detalle, confirmar_acciones
            $table->text('valor'); // ej: compacto, alto, siempre
            $table->timestamps();

            $table->unique(['user_id', 'clave']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_preferencias_usuario');
        Schema::dropIfExists('chat_estado_entidades');
        Schema::dropIfExists('chat_memoria_sesion');
        Schema::dropIfExists('chat_resumen_contexto');
    }
};
