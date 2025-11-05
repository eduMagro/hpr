<?php

namespace App\Services\PlanillaImport;

use App\Models\Etiqueta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para generación de códigos de etiquetas.
 * 
 * VERSIÓN DEFINITIVA: Corrige el problema de duplicados cuando las etiquetas padre se eliminan
 */
class CodigoEtiqueta
{
    private static ?int $contadorBatch = null;
    private static ?string $prefijoActual = null;

    /**
     * Inicializa el contador batch para una importación.
     * 
     * ✅ SOLUCIÓN DEFINITIVA: Busca códigos en TODOS los registros (padre y subetiquetas),
     * no solo en etiquetas padre, porque las etiquetas padre se eliminan después de crear subetiquetas.
     */
    public function inicializarContadorBatch(): void
    {
        $prefijo = $this->obtenerPrefijo();
        self::$prefijoActual = $prefijo;

        Log::channel('planilla_import')->info("🔍 [EtiquetaCodigo] Buscando último código con prefijo: {$prefijo}");

        // ✅ SOLUCIÓN DEFINITIVA: Buscar en TODOS los registros, no solo en etiquetas padre
        // Extrae el número base del código (antes del punto si hay subetiqueta)
        // Ejemplos:
        //   ETQ2511001 → 1
        //   ETQ2511001.01 → 1 (ignora lo que hay después del punto)
        //   ETQ2511010.05 → 10
        $ultimo = DB::table('etiquetas')
            ->where('codigo', 'like', "{$prefijo}%")
            ->selectRaw("
                codigo,
                CAST(
                    CASE 
                        WHEN INSTR(codigo, '.') > 0 
                        THEN SUBSTRING(codigo, 8, INSTR(codigo, '.') - 8)
                        ELSE SUBSTRING(codigo, 8)
                    END 
                AS UNSIGNED) as numero_base
            ")
            ->orderByDesc('numero_base')
            ->lockForUpdate()
            ->first();

        if ($ultimo && $ultimo->numero_base > 0) {
            $numero = (int)$ultimo->numero_base;
            self::$contadorBatch = $numero;

            Log::channel('planilla_import')->info(
                "🔄 [EtiquetaCodigo] Contador batch inicializado: último código base = {$ultimo->codigo} (número extraído: {$numero})"
            );
        } else {
            self::$contadorBatch = 0;

            Log::channel('planilla_import')->info(
                "🆕 [EtiquetaCodigo] Contador batch inicializado: no hay códigos previos para {$prefijo}, empezando en 0"
            );
        }
    }

    /**
     * Resetea el contador batch.
     */
    public function resetearContadorBatch(): void
    {
        $contador = self::$contadorBatch ?? 'null';

        Log::channel('planilla_import')->info(
            "🔄 [EtiquetaCodigo] Contador batch reseteado (era: {$contador})"
        );

        self::$contadorBatch = null;
        self::$prefijoActual = null;
    }

    /**
     * Genera un código de etiqueta padre.
     */
    public function generarCodigoPadre(): string
    {
        $prefijo = $this->obtenerPrefijo();

        // ✅ Si hay contador en memoria, usarlo (estamos en un batch)
        if (self::$contadorBatch !== null && self::$prefijoActual === $prefijo) {
            self::$contadorBatch++;
            $codigo = sprintf('%s%03d', $prefijo, self::$contadorBatch);

            Log::channel('planilla_import')->debug(
                "🔢 [EtiquetaCodigo] Código generado desde contador batch: {$codigo}"
            );

            return $codigo;
        }

        // ✅ Sin contador, consultar BD (modo legacy)
        return DB::transaction(function () use ($prefijo) {
            // ✅ SOLUCIÓN DEFINITIVA: Buscar en TODOS los registros
            $ultimo = DB::table('etiquetas')
                ->where('codigo', 'like', "{$prefijo}%")
                ->selectRaw("
                    codigo,
                    CAST(
                        CASE 
                            WHEN INSTR(codigo, '.') > 0 
                            THEN SUBSTRING(codigo, 8, INSTR(codigo, '.') - 8)
                            ELSE SUBSTRING(codigo, 8)
                        END 
                    AS UNSIGNED) as numero_base
                ")
                ->orderByDesc('numero_base')
                ->lockForUpdate()
                ->first();

            $numero = ($ultimo && $ultimo->numero_base > 0)
                ? (int)$ultimo->numero_base + 1
                : 1;

            $codigo = sprintf('%s%03d', $prefijo, $numero);

            Log::channel('planilla_import')->debug(
                "🔢 [EtiquetaCodigo] Código generado desde BD: {$codigo} (último: " . ($ultimo ? $ultimo->codigo : 'ninguno') . ")"
            );

            return $codigo;
        });
    }

    /**
     * Genera un código de subetiqueta.
     */
    public function generarCodigoSubetiqueta(string $codigoPadre): string
    {
        return DB::transaction(function () use ($codigoPadre) {
            $prefijo = $codigoPadre . '.';

            // Buscar el máximo índice de subetiqueta
            $maxIndice = Etiqueta::where('etiqueta_sub_id', 'like', $prefijo . '%')
                ->lockForUpdate()
                ->selectRaw("MAX(CAST(SUBSTRING_INDEX(etiqueta_sub_id, '.', -1) AS UNSIGNED)) as maxnum")
                ->value('maxnum');

            $siguiente = ($maxIndice ? ((int)$maxIndice) : 0) + 1;
            $subId = $codigoPadre . '.' . str_pad($siguiente, 2, '0', STR_PAD_LEFT);

            Log::channel('planilla_import')->debug(
                "🔢 [EtiquetaCodigo] Subetiqueta generada: {$subId} (padre: {$codigoPadre}, índice: {$siguiente})"
            );

            return $subId;
        });
    }

    /**
     * Obtiene el prefijo del mes actual.
     */
    protected function obtenerPrefijo(): string
    {
        return 'ETQ' . now()->format('ym');
    }

    /**
     * Obtiene información de diagnóstico del estado actual.
     */
    public function obtenerDiagnostico(): array
    {
        $prefijo = $this->obtenerPrefijo();

        // ✅ Buscar en TODOS los registros
        $ultimo = DB::table('etiquetas')
            ->where('codigo', 'like', "{$prefijo}%")
            ->selectRaw("
                codigo,
                etiqueta_sub_id,
                CAST(
                    CASE 
                        WHEN INSTR(codigo, '.') > 0 
                        THEN SUBSTRING(codigo, 8, INSTR(codigo, '.') - 8)
                        ELSE SUBSTRING(codigo, 8)
                    END 
                AS UNSIGNED) as numero_base
            ")
            ->orderByDesc('numero_base')
            ->first();

        return [
            'prefijo_actual' => $prefijo,
            'contador_en_memoria' => self::$contadorBatch,
            'ultimo_codigo_bd' => $ultimo ? $ultimo->codigo : null,
            'ultimo_es_subetiqueta' => $ultimo ? ($ultimo->etiqueta_sub_id !== null && $ultimo->etiqueta_sub_id !== '') : false,
            'numero_en_bd' => $ultimo ? (int)$ultimo->numero_base : 0,
            'proximo_codigo' => self::$contadorBatch !== null
                ? sprintf('%s%03d', $prefijo, self::$contadorBatch + 1)
                : ($ultimo ? sprintf('%s%03d', $prefijo, (int)$ultimo->numero_base + 1) : "{$prefijo}001"),
            'modo' => self::$contadorBatch !== null ? 'batch' : 'individual',
        ];
    }

    /**
     * Verifica si hay códigos duplicados en el mes actual.
     */
    public function verificarDuplicados(): array
    {
        $prefijo = $this->obtenerPrefijo();

        // Verificar duplicados en códigos BASE (ignorando subetiquetas)
        $duplicados = DB::table('etiquetas')
            ->where('codigo', 'like', "{$prefijo}%")
            ->selectRaw("
                codigo,
                CAST(
                    CASE 
                        WHEN INSTR(codigo, '.') > 0 
                        THEN SUBSTRING(codigo, 8, INSTR(codigo, '.') - 8)
                        ELSE SUBSTRING(codigo, 8)
                    END 
                AS UNSIGNED) as numero_base,
                COUNT(*) as cantidad_con_este_numero
            ")
            ->groupBy('numero_base', 'codigo')
            ->havingRaw('COUNT(DISTINCT CASE 
                WHEN etiqueta_sub_id IS NULL OR etiqueta_sub_id = "" 
                THEN "padre" 
                ELSE "sub" 
            END) > 1')
            ->get();

        return $duplicados->map(fn($row) => [
            'codigo_base' => sprintf('%s%03d', $prefijo, $row->numero_base),
            'ejemplo_codigo' => $row->codigo,
            'cantidad' => $row->cantidad_con_este_numero
        ])->toArray();
    }

    /**
     * Obtiene estadísticas del mes actual.
     */
    public function obtenerEstadisticas(): array
    {
        $prefijo = $this->obtenerPrefijo();

        $totalPadre = Etiqueta::where('codigo', 'like', "{$prefijo}%")
            ->where(function ($q) {
                $q->whereNull('etiqueta_sub_id')
                    ->orWhere('etiqueta_sub_id', '');
            })
            ->count();

        $totalSub = Etiqueta::where('codigo', 'like', "{$prefijo}%")
            ->whereRaw("etiqueta_sub_id LIKE '%.%'")
            ->count();

        // ✅ Contar códigos únicos (números base)
        $codigosUnicos = DB::table('etiquetas')
            ->where('codigo', 'like', "{$prefijo}%")
            ->selectRaw("
                COUNT(DISTINCT 
                    CASE 
                        WHEN INSTR(codigo, '.') > 0 
                        THEN SUBSTRING(codigo, 8, INSTR(codigo, '.') - 8)
                        ELSE SUBSTRING(codigo, 8)
                    END
                ) as total_unicos
            ")
            ->value('total_unicos');

        $duplicados = $this->verificarDuplicados();

        return [
            'mes_actual' => now()->format('Y-m'),
            'prefijo' => $prefijo,
            'total_etiquetas_padre' => $totalPadre,
            'total_subetiquetas' => $totalSub,
            'total_registros' => $totalPadre + $totalSub,
            'codigos_base_unicos' => (int)$codigosUnicos,
            'duplicados' => count($duplicados),
            'lista_duplicados' => $duplicados,
        ];
    }
}
