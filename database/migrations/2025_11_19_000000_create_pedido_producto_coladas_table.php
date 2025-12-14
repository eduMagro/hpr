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
        if (Schema::hasTable('pedido_producto_coladas')) {
            return;
        }

        Schema::create('pedido_producto_coladas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_producto_id');
            $table->string('colada')->nullable();
            $table->decimal('bulto', 15, 3)->nullable();
            $table->timestamps();

            $table->foreign('pedido_producto_id')
                ->references('id')
                ->on('pedido_productos')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_producto_coladas');
    }
};
