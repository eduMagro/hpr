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
        // Índices para chat_conversaciones
        Schema::table('chat_conversaciones', function (Blueprint $table) {
            // Índice para buscar conversaciones por usuario y fecha
            $table->index(['user_id', 'ultima_actividad'], 'idx_user_actividad');
        });

        // Índices para chat_mensajes
        Schema::table('chat_mensajes', function (Blueprint $table) {
            // Índice para buscar mensajes por conversación
            $table->index('conversacion_id', 'idx_conversacion');

            // Índice compuesto para filtrar por conversación y rol
            $table->index(['conversacion_id', 'role'], 'idx_conversacion_role');

            // Índice para ordenar por fecha
            $table->index('created_at', 'idx_created_at');
        });

        // Índices para chat_consultas_sql
        Schema::table('chat_consultas_sql', function (Blueprint $table) {
            // Índice para buscar consultas por usuario
            $table->index('user_id', 'idx_user');

            // Índice compuesto para auditoría (consultas exitosas por usuario y fecha)
            $table->index(['user_id', 'exitosa', 'created_at'], 'idx_auditoria');

            // Índice para buscar por tipo de operación
            $table->index('consulta_sql', 'idx_consulta', null, 'fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_conversaciones', function (Blueprint $table) {
            $table->dropIndex('idx_user_actividad');
        });

        Schema::table('chat_mensajes', function (Blueprint $table) {
            $table->dropIndex('idx_conversacion');
            $table->dropIndex('idx_conversacion_role');
            $table->dropIndex('idx_created_at');
        });

        Schema::table('chat_consultas_sql', function (Blueprint $table) {
            $table->dropIndex('idx_user');
            $table->dropIndex('idx_auditoria');
            $table->dropIndex('idx_consulta');
        });
    }
};
