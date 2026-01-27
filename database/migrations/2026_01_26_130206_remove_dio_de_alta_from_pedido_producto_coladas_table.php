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
        Schema::table('pedido_producto_coladas', function (Blueprint $table) {
            $table->dropForeign(['dio_de_alta']);
            $table->dropColumn('dio_de_alta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_producto_coladas', function (Blueprint $table) {
            $table->unsignedBigInteger('dio_de_alta')->nullable()->after('user_id');
            $table->foreign('dio_de_alta')->references('id')->on('users')->onDelete('set null');
        });
    }
};
