<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            if (!Schema::hasColumn('alertas', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            }

            $hasForeign = collect(DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'alertas'
                  AND TABLE_SCHEMA = DATABASE()
                  AND COLUMN_NAME = 'parent_id'
                  AND REFERENCED_TABLE_NAME = 'alertas'
            "))->isNotEmpty();

            if (!$hasForeign) {
                $table->foreign('parent_id')
                    ->references('id')
                    ->on('alertas')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            if (Schema::hasColumn('alertas', 'parent_id')) {
                try {
                    $table->dropForeign(['parent_id']);
                } catch (\Throwable $e) {
                    // Ignorar si no existe la restricción
                }
                $table->dropColumn('parent_id');
            }
        });
    }
};
