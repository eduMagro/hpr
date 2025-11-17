<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Parser para leer y analizar logs de fabricación en CSV
 */
class ProductionLogParser
{
    /**
     * Obtiene todos los logs de una etiqueta específica
     */
    public static function getLogsForEtiqueta(int $etiquetaId, ?string $month = null): Collection
    {
        $month = $month ?? date('Y_m');
        $logs = self::readCsvFile($month);

        return $logs->filter(function ($log) use ($etiquetaId) {
            return str_contains($log['detalles'], "Etiq#{$etiquetaId}");
        });
    }

    /**
     * Obtiene logs de asignación de coladas para una etiqueta
     */
    public static function getAsignacionColadasForEtiqueta(int $etiquetaId, ?string $month = null): ?array
    {
        $logs = self::getLogsForEtiqueta($etiquetaId, $month);

        $logAsignacion = $logs->firstWhere('tipo', 'ASIGNACION_COLADAS');

        if (!$logAsignacion) {
            return null;
        }

        return self::parseAsignacionColadas($logAsignacion);
    }

    /**
     * Obtiene logs de consumo de stock para una etiqueta
     */
    public static function getConsumoStockForEtiqueta(int $etiquetaId, ?string $month = null): ?array
    {
        $logs = self::getLogsForEtiqueta($etiquetaId, $month);

        $logConsumo = $logs->firstWhere('tipo', 'CONSUMO_STOCK');

        if (!$logConsumo) {
            return null;
        }

        return self::parseConsumoStock($logConsumo);
    }

    /**
     * Obtiene todos los elementos fabricados con una colada específica
     */
    public static function getElementsByColada(string $colada, ?string $month = null): Collection
    {
        $month = $month ?? date('Y_m');
        $logs = self::readCsvFile($month);

        $resultado = collect();

        foreach ($logs as $log) {
            if ($log['tipo'] !== 'ASIGNACION_COLADAS') {
                continue;
            }

            if (str_contains($log['detalles'], "Colada:{$colada}")) {
                $parsed = self::parseAsignacionColadas($log);

                foreach ($parsed['elementos'] as $elemento) {
                    foreach ($elemento['coladas'] as $coladaInfo) {
                        if ($coladaInfo['n_colada'] === $colada) {
                            $resultado->push([
                                'etiqueta_id' => $parsed['etiqueta_id'],
                                'timestamp' => $log['timestamp'],
                                'elemento' => $elemento,
                            ]);
                        }
                    }
                }
            }
        }

        return $resultado;
    }

    /**
     * Obtiene estadísticas de un mes
     */
    public static function getStats(?string $month = null): array
    {
        $month = $month ?? date('Y_m');
        $logs = self::readCsvFile($month);

        $stats = [
            'total_etiquetas' => 0,
            'total_elementos' => 0,
            'asignaciones_simples' => 0,
            'asignaciones_dobles' => 0,
            'asignaciones_triples' => 0,
            'warnings' => 0,
            'coladas_utilizadas' => collect(),
            'consumo_por_diametro' => [],
        ];

        foreach ($logs as $log) {
            if ($log['tipo'] === 'ASIGNACION_COLADAS') {
                $parsed = self::parseAsignacionColadas($log);
                $stats['total_etiquetas']++;
                $stats['total_elementos'] += $parsed['total_elementos'];
                $stats['asignaciones_simples'] += $parsed['stats']['simple'];
                $stats['asignaciones_dobles'] += $parsed['stats']['doble'];
                $stats['asignaciones_triples'] += $parsed['stats']['triple'];

                if ($parsed['warnings']) {
                    $stats['warnings']++;
                }

                // Recolectar coladas
                foreach ($parsed['elementos'] as $elemento) {
                    foreach ($elemento['coladas'] as $colada) {
                        if (!$stats['coladas_utilizadas']->contains($colada['n_colada'])) {
                            $stats['coladas_utilizadas']->push($colada['n_colada']);
                        }
                    }
                }
            }

            if ($log['tipo'] === 'CONSUMO_STOCK') {
                $parsed = self::parseConsumoStock($log);
                foreach ($parsed['diametros'] as $diametro => $info) {
                    if (!isset($stats['consumo_por_diametro'][$diametro])) {
                        $stats['consumo_por_diametro'][$diametro] = 0;
                    }
                    $stats['consumo_por_diametro'][$diametro] += $info['total_kg'];
                }
            }
        }

        return $stats;
    }

    /**
     * Lee el archivo CSV de un mes específico
     */
    protected static function readCsvFile(string $month): Collection
    {
        $filePath = "produccion_piezas/fabricacion_{$month}.csv";

        if (!Storage::exists($filePath)) {
            return collect();
        }

        $content = Storage::get($filePath);
        $lines = explode("\n", $content);
        $logs = collect();

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Parsear CSV manualmente (maneja comillas)
            $matches = [];
            if (preg_match('/^"([^"]+)","([^"]+)","(.+)"$/', $line, $matches)) {
                $logs->push([
                    'timestamp' => $matches[1],
                    'tipo' => $matches[2],
                    'detalles' => $matches[3],
                ]);
            }
        }

        return $logs;
    }

    /**
     * Parsea un log de asignación de coladas
     */
    protected static function parseAsignacionColadas(array $log): array
    {
        $detalles = $log['detalles'];

        // Extraer información básica
        preg_match('/Etiq#(\d+)/', $detalles, $etiquetaMatch);
        preg_match('/Maq:([^|]+)/', $detalles, $maquinaMatch);
        preg_match('/(\d+) elementos? fabricados?/', $detalles, $totalMatch);

        // Extraer estadísticas
        preg_match('/Simple:(\d+), Doble:(\d+), Triple:(\d+)/', $detalles, $statsMatch);

        // Extraer warnings
        $hasWarnings = str_contains($detalles, 'WARNING');

        // Extraer elementos individuales
        $elementos = [];
        preg_match_all('/Elem(\d+)\[Ø(\d+)mm,([\d.]+)kg\]→([^|]+)/', $detalles, $elementosMatches, PREG_SET_ORDER);

        foreach ($elementosMatches as $elemMatch) {
            $elementoId = $elemMatch[1];
            $diametro = $elemMatch[2];
            $peso = $elemMatch[3];
            $asignacionText = $elemMatch[4];

            // Parsear productos/coladas asignadas
            $coladas = [];
            preg_match_all('/P(\d+)\(Colada:([^,)]+),([\d.]+)kg\)/', $asignacionText, $coladasMatches, PREG_SET_ORDER);

            foreach ($coladasMatches as $coladaMatch) {
                $coladas[] = [
                    'producto_id' => (int) $coladaMatch[1],
                    'n_colada' => $coladaMatch[2],
                    'peso_consumido' => (float) $coladaMatch[3],
                ];
            }

            $elementos[] = [
                'elemento_id' => (int) $elementoId,
                'diametro' => (int) $diametro,
                'peso' => (float) $peso,
                'coladas' => $coladas,
                'tipo_asignacion' => count($coladas) === 1 ? 'simple' : (count($coladas) === 2 ? 'doble' : 'triple'),
            ];
        }

        return [
            'timestamp' => $log['timestamp'],
            'etiqueta_id' => isset($etiquetaMatch[1]) ? (int) $etiquetaMatch[1] : null,
            'maquina' => isset($maquinaMatch[1]) ? trim($maquinaMatch[1]) : null,
            'total_elementos' => isset($totalMatch[1]) ? (int) $totalMatch[1] : count($elementos),
            'stats' => [
                'simple' => isset($statsMatch[1]) ? (int) $statsMatch[1] : 0,
                'doble' => isset($statsMatch[2]) ? (int) $statsMatch[2] : 0,
                'triple' => isset($statsMatch[3]) ? (int) $statsMatch[3] : 0,
            ],
            'warnings' => $hasWarnings,
            'elementos' => $elementos,
        ];
    }

    /**
     * Parsea un log de consumo de stock
     */
    protected static function parseConsumoStock(array $log): array
    {
        $detalles = $log['detalles'];

        // Extraer información básica
        preg_match('/Etiq#(\d+)/', $detalles, $etiquetaMatch);
        preg_match('/Maq:([^|]+)/', $detalles, $maquinaMatch);

        // Extraer consumos por diámetro
        $diametros = [];
        preg_match_all('/Ø(\d+)mm:([\d.]+)kg\[(\d+) productos?:([^\]]+)\]/', $detalles, $diametrosMatches, PREG_SET_ORDER);

        foreach ($diametrosMatches as $diamMatch) {
            $diametro = $diamMatch[1];
            $totalKg = $diamMatch[2];
            $numProductos = $diamMatch[3];
            $productosText = $diamMatch[4];

            // Parsear productos individuales
            $productos = [];
            preg_match_all('/P(\d+):([\d.]+)kg/', $productosText, $productsMatches, PREG_SET_ORDER);

            foreach ($productsMatches as $prodMatch) {
                $productos[] = [
                    'producto_id' => (int) $prodMatch[1],
                    'consumido' => (float) $prodMatch[2],
                ];
            }

            $diametros[$diametro] = [
                'total_kg' => (float) $totalKg,
                'num_productos' => (int) $numProductos,
                'productos' => $productos,
            ];
        }

        return [
            'timestamp' => $log['timestamp'],
            'etiqueta_id' => isset($etiquetaMatch[1]) ? (int) $etiquetaMatch[1] : null,
            'maquina' => isset($maquinaMatch[1]) ? trim($maquinaMatch[1]) : null,
            'diametros' => $diametros,
        ];
    }

    /**
     * Obtiene lista de meses disponibles
     */
    public static function getAvailableMonths(): Collection
    {
        $files = Storage::files('produccion_piezas');

        return collect($files)
            ->filter(fn($file) => str_starts_with(basename($file), 'fabricacion_'))
            ->map(function ($file) {
                $basename = basename($file);
                preg_match('/fabricacion_(\d{4}_\d{2})\.csv/', $basename, $matches);
                return $matches[1] ?? null;
            })
            ->filter()
            ->sort()
            ->reverse()
            ->values();
    }
}
