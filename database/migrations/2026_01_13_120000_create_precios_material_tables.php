<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas para configurar las reglas de precios de material.
     *
     * Sistema de cálculo:
     * Coste = (precio_referencia + incremento_diametro + incremento_formato) × toneladas
     *
     * Donde:
     * - precio_referencia: viene del PedidoGlobal (Ø16 a 12m como base)
     * - incremento_diametro: según el diámetro del producto
     * - incremento_formato: según el formato (estándar, largo especial, corto, encarretado)
     */
    public function up(): void
    {
        // Tabla de incrementos por diámetro (igual para todos los proveedores)
        Schema::create('precios_material_diametros', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('diametro')->unique();
            $table->decimal('incremento', 10, 2)->default(0); // €/tonelada
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Tabla de incrementos por formato
        Schema::create('precios_material_formatos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique(); // estandar_12m, largo_especial, corto_6m, encarretado
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->decimal('longitud_min', 6, 2)->nullable(); // metros, null = sin límite
            $table->decimal('longitud_max', 6, 2)->nullable(); // metros, null = sin límite
            $table->boolean('es_encarretado')->default(false);
            $table->decimal('incremento_general', 10, 2)->default(0); // €/tonelada para proveedores generales
            $table->decimal('incremento_siderurgica', 10, 2)->default(0); // €/tonelada para Siderúrgica Sevillana
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Tabla de excepciones (ej: Sufealsa + Siderúrgica)
        Schema::create('precios_material_excepciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribuidor_id')->constrained('distribuidores')->cascadeOnDelete();
            $table->foreignId('fabricante_id')->constrained('fabricantes')->cascadeOnDelete();
            $table->string('formato_codigo', 30); // referencia a precios_material_formatos.codigo
            $table->decimal('incremento', 10, 2); // €/tonelada a aplicar
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['distribuidor_id', 'fabricante_id', 'formato_codigo'], 'excepcion_unica');
        });

        // Configuración general
        Schema::create('precios_material_config', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 50)->unique();
            $table->string('valor', 255);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        // Añadir campo para identificar Siderúrgica Sevillana en fabricantes
        if (!Schema::hasColumn('fabricantes', 'es_siderurgica_sevillana')) {
            Schema::table('fabricantes', function (Blueprint $table) {
                $table->boolean('es_siderurgica_sevillana')->default(false)->after('direccion');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precios_material_excepciones');
        Schema::dropIfExists('precios_material_formatos');
        Schema::dropIfExists('precios_material_diametros');
        Schema::dropIfExists('precios_material_config');

        if (Schema::hasColumn('fabricantes', 'es_siderurgica_sevillana')) {
            Schema::table('fabricantes', function (Blueprint $table) {
                $table->dropColumn('es_siderurgica_sevillana');
            });
        }
    }
};
