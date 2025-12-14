<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epi_compra_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('epi_compras')->cascadeOnDelete();
            $table->foreignId('epi_id')->constrained('epis')->restrictOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('precio_unitario', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['compra_id', 'epi_id']);
            $table->index(['epi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epi_compra_items');
    }
};

