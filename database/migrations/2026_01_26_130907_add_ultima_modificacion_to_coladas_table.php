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
            $table->unsignedBigInteger('ultima_modificacion')->nullable()->after('dio_de_alta');
            $table->foreign('ultima_modificacion')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coladas', function (Blueprint $table) {
            $table->dropForeign(['ultima_modificacion']);
            $table->dropColumn('ultima_modificacion');
        });
    }
};
