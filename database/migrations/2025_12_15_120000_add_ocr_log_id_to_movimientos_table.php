<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('movimientos')) {
            return;
        }

        Schema::table('movimientos', function (Blueprint $table) {
            if (Schema::hasColumn('movimientos', 'ocr_log_id')) {
                return;
            }

            $table->unsignedBigInteger('ocr_log_id')->nullable()->after('pedido_producto_id');
            $table->index('ocr_log_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('movimientos')) {
            return;
        }

        Schema::table('movimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('movimientos', 'ocr_log_id')) {
                return;
            }

            $table->dropIndex(['ocr_log_id']);
            $table->dropColumn('ocr_log_id');
        });
    }
};
