<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activacion_pedido_automatizada')) {
            return;
        }

        Schema::create('activacion_pedido_automatizada', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ocr_log_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->json('json_original')->nullable();
            $table->json('json_resultante')->nullable();

            $table->boolean('editado')->default(false);

            $table->string('seleccion_pedido', 20)->nullable(); // recomendado|manual
            $table->unsignedBigInteger('id_pedido_productos_recomendado')->nullable()->index();
            $table->unsignedBigInteger('id_pedido_productos_seleccion_manual')->nullable()->index();

            $table->timestamps();

            // MySQL has a 64-char identifier limit; use short FK names.
            $table->foreign('ocr_log_id', 'apaa_ocr_fk')->references('id')->on('entrada_import_logs')->nullOnDelete();
            $table->foreign('user_id', 'apaa_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('id_pedido_productos_recomendado', 'apaa_reco_fk')->references('id')->on('pedido_productos')->nullOnDelete();
            $table->foreign('id_pedido_productos_seleccion_manual', 'apaa_manual_fk')->references('id')->on('pedido_productos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activacion_pedido_automatizada');
    }
};
