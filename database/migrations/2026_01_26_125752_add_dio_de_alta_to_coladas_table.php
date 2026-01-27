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
        Schema::table('coladas', function (Blueprint $table) {
            $table->unsignedBigInteger('dio_de_alta')->nullable()->after('fabricante_id');
            $table->foreign('dio_de_alta')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coladas', function (Blueprint $table) {
            $table->dropForeign(['dio_de_alta']);
            $table->dropColumn('dio_de_alta');
        });
    }
};
