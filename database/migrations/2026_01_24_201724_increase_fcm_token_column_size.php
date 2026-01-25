<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Aumenta el tamaño de la columna token para soportar tokens FCM largos.
     * Los tokens FCM pueden superar los 255 caracteres.
     */
    public function up(): void
    {
        // Eliminar índice único actual
        DB::statement('ALTER TABLE user_fcm_tokens DROP INDEX user_fcm_tokens_token_unique');

        // Cambiar columna a TEXT
        DB::statement('ALTER TABLE user_fcm_tokens MODIFY token TEXT NOT NULL');

        // Recrear índice único con prefijo (TEXT no permite índice completo)
        DB::statement('ALTER TABLE user_fcm_tokens ADD UNIQUE INDEX user_fcm_tokens_token_unique (token(255))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE user_fcm_tokens DROP INDEX user_fcm_tokens_token_unique');
        DB::statement('ALTER TABLE user_fcm_tokens MODIFY token VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE user_fcm_tokens ADD UNIQUE INDEX user_fcm_tokens_token_unique (token)');
    }
};
