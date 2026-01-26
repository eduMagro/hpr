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
        Schema::create('distribuidor_direcciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('distribuidor_id');
            // Almacena el texto escaneado/ocr (normalizado) que identifica la dirección/proveedor
            $table->text('direccion_match');
            $table->timestamps();

            $table->foreign('distribuidor_id')->references('id')->on('distribuidores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribuidor_direcciones');
    }
};
