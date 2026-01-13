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
        Schema::table('etiquetas_ensamblaje', function (Blueprint $table) {
            $table->string('codigo', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etiquetas_ensamblaje', function (Blueprint $table) {
            $table->string('codigo', 50)->change();
        });
    }
};
