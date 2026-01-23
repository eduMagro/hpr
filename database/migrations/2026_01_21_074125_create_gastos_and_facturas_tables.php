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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('ruta')->nullable();
            $table->timestamps();
        });

        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_pedido')->nullable();
            $table->date('fecha_llegada')->nullable();
            $table->foreignId('nave_id')->nullable()->constrained('obras')->nullOnDelete();
            $table->foreignId('obra_id')->nullable()->constrained('obras')->nullOnDelete();
            $table->string('proveedor')->nullable();
            $table->foreignId('maquina_id')->nullable()->constrained('maquinas')->nullOnDelete();
            $table->string('motivo')->nullable();
            $table->decimal('coste', 10, 2)->nullable();
            $table->foreignId('factura_id')->nullable()->constrained('facturas')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
        Schema::dropIfExists('facturas');
    }
};
