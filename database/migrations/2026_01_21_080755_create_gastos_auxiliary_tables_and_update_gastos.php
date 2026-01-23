<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create auxiliary tables
        Schema::create('gastos_proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        Schema::create('gastos_motivos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // 2. Modifying gastos table
        Schema::table('gastos', function (Blueprint $table) {
            $table->foreignId('proveedor_id')->nullable()->after('obra_id')->constrained('gastos_proveedores')->nullOnDelete();
            $table->foreignId('motivo_id')->nullable()->after('maquina_id')->constrained('gastos_motivos')->nullOnDelete();
        });

        // 3. Migrate existing data (if any)
        // This is a "best effort" migration. Text values that don't match exactly existing ones will create new entries.
        $existingGastos = DB::table('gastos')->get();
        foreach ($existingGastos as $gasto) {
            $provId = null;
            if ($gasto->proveedor) {
                $provId = DB::table('gastos_proveedores')->insertGetId([
                    'nombre' => $gasto->proveedor,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            $motId = null;
            if ($gasto->motivo) {
                $motId = DB::table('gastos_motivos')->insertGetId([
                    'nombre' => $gasto->motivo,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::table('gastos')->where('id', $gasto->id)->update([
                'proveedor_id' => $provId,
                'motivo_id' => $motId
            ]);
        }

        // 4. Drop old columns
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropColumn('proveedor');
            $table->dropColumn('motivo');
        });

        // 5. Seed initial values requested by user
        $proveedores = [
            'PROGRESS',
            'ARGEMAQ MACHINES S.L',
            'BIGMAT',
            'grupo portillo',
            'DAKO RETAIL, S.L',
            'RIGMSUR S.L',
            'PONCESUR',
            'FONTANERIA Y AZULEJOS RODRIGUEZ S.L',
            'SOLDADURAS DE ANDALUCÍA S.L',
            'HIERROS PACO REYES S.L.U',
            'ELEKTRA ANDALUCIA XXI S.L',
            'RODAMIENTOS BLANCO,S.L',
            'RODAMIENTOS PEREIRA',
            'COMERCIO JAILIN S.L',
            'PRONOVA SOLUCIONES INDUSTRIALES',
            'SATEL',
            'RADIADORES GARRINCHA',
            'MECANIZADOS R. LÓPEZ E HIJOS, S.L',
            'PASCUAL BLANCH'
        ];

        foreach ($proveedores as $p) {
            DB::table('gastos_proveedores')->insertOrIgnore([
                'nombre' => $p,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $motivos = [
            'AVERÍA',
            'PRODUCCIÓN',
            'MEJORA',
            'ACOND. OBRA',
            'EPI´S',
            'BOTAS SEGURIDAD',
            'LIMPIEZA NAVE/SERVICIOS',
            'OBRAS NAVE',
            'RECUENTO',
            'MONTAJE MÁQUINAS',
            'HILO SOLDAR',
            'FILTRO GASOIL',
            'MANTENIMIENTO',
            'MONTAJE RED ELÉCTRICA'
        ];

        foreach ($motivos as $m) {
            DB::table('gastos_motivos')->insertOrIgnore([
                'nombre' => $m,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->string('proveedor')->nullable();
            $table->string('motivo')->nullable();
            $table->dropForeign(['proveedor_id']);
            $table->dropForeign(['motivo_id']);
            $table->dropColumn('proveedor_id');
            $table->dropColumn('motivo_id');
        });

        Schema::dropIfExists('gastos_proveedores');
        Schema::dropIfExists('gastos_motivos');
    }
};
