<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('incorporacion_documentos', function (Blueprint $table) {
            // Check if index exists before dropping? Schema::hasIndex is not standard.
            // Using try-catch or raw sql might be safer, but dropUnique usually works if exists.
            // We'll rely on Illuminate logic.

            // To be safe against "Drop failed check that column/key exists", we can just try.
            try {
                $table->dropUnique('uk_incorporacion_tipo');
            } catch (\Exception $e) {
                // Ignore if it doesn't exist
            }
        });
    }

    public function down()
    {
        Schema::table('incorporacion_documentos', function (Blueprint $table) {
            // Re-adding it would be problematic if duplicates exist now.
            // So we might leave down empty or try to add it back only if no duplicates.
        });
    }
};
