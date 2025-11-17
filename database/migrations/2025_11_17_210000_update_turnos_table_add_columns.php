<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            if (!Schema::hasColumn('turnos', 'hora_inicio')) {
                $table->time('hora_inicio')->nullable()->after('nombre');
            }

            if (!Schema::hasColumn('turnos', 'hora_fin')) {
                $table->time('hora_fin')->nullable()->after('hora_inicio');
            }

            if (!Schema::hasColumn('turnos', 'offset_dias_inicio')) {
                $table->tinyInteger('offset_dias_inicio')->default(0)->after('hora_fin');
            }

            if (!Schema::hasColumn('turnos', 'offset_dias_fin')) {
                $table->tinyInteger('offset_dias_fin')->default(0)->after('offset_dias_inicio');
            }

            if (!Schema::hasColumn('turnos', 'activo')) {
                $table->boolean('activo')->default(true)->after('offset_dias_fin');
            }

            if (!Schema::hasColumn('turnos', 'orden')) {
                $table->integer('orden')->default(0)->after('activo');
            }

            if (!Schema::hasColumn('turnos', 'color')) {
                $table->string('color', 7)->nullable()->after('orden');
            }
        });

        if (Schema::hasColumn('turnos', 'hora_entrada') && Schema::hasColumn('turnos', 'hora_inicio')) {
            DB::statement('UPDATE `turnos` SET `hora_inicio` = `hora_entrada` WHERE `hora_inicio` IS NULL AND `hora_entrada` IS NOT NULL');
        }

        if (Schema::hasColumn('turnos', 'hora_salida') && Schema::hasColumn('turnos', 'hora_fin')) {
            DB::statement('UPDATE `turnos` SET `hora_fin` = `hora_salida` WHERE `hora_fin` IS NULL AND `hora_salida` IS NOT NULL');
        }

        if (Schema::hasColumn('turnos', 'entrada_offset') && Schema::hasColumn('turnos', 'offset_dias_inicio')) {
            DB::statement('UPDATE `turnos` SET `offset_dias_inicio` = `entrada_offset`');
        }

        if (Schema::hasColumn('turnos', 'salida_offset') && Schema::hasColumn('turnos', 'offset_dias_fin')) {
            DB::statement('UPDATE `turnos` SET `offset_dias_fin` = `salida_offset`');
        }

        if (Schema::hasColumn('turnos', 'activo')) {
            DB::table('turnos')->whereNull('activo')->update(['activo' => true]);
        }

        if (Schema::hasColumn('turnos', 'orden')) {
            DB::statement('UPDATE `turnos` SET `orden` = `id` WHERE `orden` = 0 OR `orden` IS NULL');
        }

        if (Schema::hasColumn('turnos', 'activo')) {
            $hasIndex = collect(DB::select("SHOW INDEX FROM `turnos` WHERE Key_name = 'idx_turnos_activo'"))->isNotEmpty();
            if (!$hasIndex) {
                Schema::table('turnos', function (Blueprint $table) {
                    $table->index('activo', 'idx_turnos_activo');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            if (Schema::hasColumn('turnos', 'color')) {
                $table->dropColumn('color');
            }

            if (Schema::hasColumn('turnos', 'orden')) {
                $table->dropColumn('orden');
            }

            if (Schema::hasColumn('turnos', 'activo')) {
                try {
                    $table->dropIndex('idx_turnos_activo');
                } catch (\Throwable $e) {
                    // ignore if index was not created
                }
                $table->dropColumn('activo');
            }

            if (Schema::hasColumn('turnos', 'offset_dias_fin')) {
                $table->dropColumn('offset_dias_fin');
            }

            if (Schema::hasColumn('turnos', 'offset_dias_inicio')) {
                $table->dropColumn('offset_dias_inicio');
            }

            if (Schema::hasColumn('turnos', 'hora_fin')) {
                $table->dropColumn('hora_fin');
            }

            if (Schema::hasColumn('turnos', 'hora_inicio')) {
                $table->dropColumn('hora_inicio');
            }
        });
    }
};
