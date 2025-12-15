<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epis_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('epi_id')->constrained('epis')->cascadeOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->timestamp('entregado_en')->nullable()->index();
            $table->timestamp('devuelto_en')->nullable()->index();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'devuelto_en']);
            $table->index(['epi_id', 'devuelto_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epis_usuario');
    }
};

