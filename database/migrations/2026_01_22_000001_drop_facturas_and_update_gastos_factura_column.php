<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Add new column first so we can optionally preserve old values
        if (Schema::hasTable('gastos') && !Schema::hasColumn('gastos', 'codigo_factura')) {
            Schema::table('gastos', function (Blueprint $table) {
                $table->string('codigo_factura', 120)->nullable()->after('coste');
            });
        }

        // 2) Copy old factura_id to codigo_factura (best-effort) before dropping it
        if (Schema::hasTable('gastos') && Schema::hasColumn('gastos', 'factura_id') && Schema::hasColumn('gastos', 'codigo_factura')) {
            DB::table('gastos')
                ->whereNotNull('factura_id')
                ->where(function ($q) {
                    $q->whereNull('codigo_factura')->orWhere('codigo_factura', '=', '');
                })
                ->update([
                    // Let the DB handle implicit cast (keeps this migration more portable across drivers)
                    'codigo_factura' => DB::raw('factura_id'),
                ]);
        }

        // 3) Drop FK + old column
        if (Schema::hasTable('gastos') && Schema::hasColumn('gastos', 'factura_id')) {
            Schema::table('gastos', function (Blueprint $table) {
                // Name is usually `gastos_factura_id_foreign` but dropForeign handles it.
                $table->dropForeign(['factura_id']);
                $table->dropColumn('factura_id');
            });
        }

        // 4) Drop table facturas
        Schema::dropIfExists('facturas');
    }

    public function down(): void
    {
        // Recreate facturas table
        if (!Schema::hasTable('facturas')) {
            Schema::create('facturas', function (Blueprint $table) {
                $table->id();
                $table->string('ruta')->nullable();
                $table->timestamps();
            });
        }

        $hasGastos = Schema::hasTable('gastos');
        $hasFacturaId = $hasGastos && Schema::hasColumn('gastos', 'factura_id');
        $hasCodigoFactura = $hasGastos && Schema::hasColumn('gastos', 'codigo_factura');

        if ($hasGastos) {
            Schema::table('gastos', function (Blueprint $table) use ($hasFacturaId, $hasCodigoFactura) {
                if (!$hasFacturaId) {
                    $table->foreignId('factura_id')->nullable()->after('coste')->constrained('facturas')->nullOnDelete();
                }
                if ($hasCodigoFactura) {
                    $table->dropColumn('codigo_factura');
                }
            });
        }
    }
};
