<?php

namespace App\Services;

use App\Models\Elemento;
use App\Models\ProductoBase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SugeridorProductoBaseService
{
    /** Tolerancia de diámetro en mm (p.ej., 0.1 mm) */
    private const DIAM_TOL_MM = 0.1;

    /**
     * Sugerir el mejor ProductoBase para un elemento (con posible pareja).
     */
    public function sugerirParaElemento(Elemento $elemento, Collection $productosBaseCompatibles, Collection $colegasPlanilla): array
    {
        $lenA = $this->lenElemMm($elemento);
        if ($lenA <= 0) return ['ok' => false, 'reason' => 'Longitud no válida'];

        // 🔹 Normalizamos diámetro del elemento (mm)
        $diamA = $this->diamElemMm($elemento);
        if ($diamA <= 0) {
            return ['ok' => false, 'reason' => 'Diámetro de elemento no válido'];
        }

        // 🔹 Filtra PB compatibles por diámetro (además de tipo barra y longitudes activas que ya traes)
        $productosBaseCompatibles = $productosBaseCompatibles
            ->filter(fn(ProductoBase $pb) => $this->diametrosCompatibles($diamA, $this->diamPBmm($pb)))
            ->values();

        if ($productosBaseCompatibles->isEmpty()) {
            return ['ok' => false, 'reason' => 'Sin producto base compatible por diámetro'];
        }

        // 🔹 Filtra colegas compatibles por diámetro con tolerancia (refina tu filtro actual)
        $colegas = $colegasPlanilla->filter(function ($e) use ($elemento, $diamA) {
            if ($e->id === $elemento->id) return false;
            $diamB = $this->diamElemMm($e);
            return $this->diametrosCompatibles($diamA, $diamB);
        })->values();

        // Log::debug('[SugeridorPB][diametros]', [
        //     'elemento_id' => $elemento->id,
        //     'diamA_mm'    => $diamA,
        //     'PB_count_in' => $productosBaseCompatibles->count(),
        //     'colegas_filtrados' => $colegas->count(),
        // ]);

        $mejor = null;

        foreach ($productosBaseCompatibles as $pb) {
            $L = $this->longitudBarraMm($pb);
            // Log::debug('[SugeridorPB][calc]', [
            //     'pb_id'     => $pb->id,
            //     'tipo'      => $pb->tipo,
            //     'diametro'  => $pb->diametro,
            //     'diam_pb_mm' => $this->diamPBmm($pb),
            //     'long_raw'  => $pb->longitud ?? null,
            //     'long_m'    => $pb->longitud_m ?? null,
            //     'L_mm'      => $L,
            // ]);
            if ($L <= 0) continue;

            // 1) Packing simple: solo el elemento A
            $nA    = intdiv($L, $lenA);      // piezas por barra
            $usoA  = $nA * $lenA;
            $sobrA = $L - $usoA;
            $effA  = $L > 0 ? ($usoA / $L) : 0.0;

            $piezasNecesarias = $this->piezasNecesarias($elemento);
            $barrasTotalesA   = ($nA > 0) ? (int) ceil($piezasNecesarias / $nA) : $piezasNecesarias;

            $candidato = [
                'producto_base_id'   => $pb->id,
                'tipo'               => $pb->tipo ?? null,      // añadido para UI
                'diametro_mm'        => $this->diamPBmm($pb),   // 👈 útil para UI y trazas
                'longitud_barra_mm'  => $L,
                'n_por_barra'        => max($nA, 0),
                'sobrante_mm'        => $sobrA,
                'eficiencia'         => $effA,
                'barras_totales'     => max($barrasTotalesA, 1),
                'pareja'             => null,
            ];

            // 2) Intento de emparejamiento con un solo colega (misma compatibilidad de diámetro ya aplicada)
            $mejorPareja = $this->mejorPareja($lenA, $colegas, $L);
            if ($mejorPareja) {
                $nA2   = $mejorPareja['nA'];              // parejas por barra
                $nB2   = $mejorPareja['nB'];
                $usoAB = $mejorPareja['uso'];
                $sobrAB = $mejorPareja['sobr'];
                $effAB = $mejorPareja['eff'];

                // Muestra la pareja solo si no empeora
                if ($effAB >= $effA) {
                    $candidato['pareja'] = [
                        'elemento_id' => $mejorPareja['idB'],
                        'lenB_mm'     => $mejorPareja['lenB'],
                        'n_por_barra' => ['A' => $nA2, 'B' => $nB2],
                        'sobrante_mm' => $sobrAB,
                        'eficiencia'  => $effAB,
                    ];

                    // si decides que la tarjeta refleje el “mix” cuando mejora:
                    $candidato['n_por_barra'] = $nA2 + $nB2;   // opcional
                    $candidato['sobrante_mm'] = $sobrAB;       // opcional
                    $candidato['eficiencia']  = $effAB;        // opcional
                }

                // Para la comparación que decide el mejor candidato:
                $candidato['_effComparacion']  = max($effA, $effAB);
                $candidato['_sobrComparacion'] = ($effAB > $effA) ? $sobrAB : $sobrA;
            } else {
                $candidato['_effComparacion']  = $effA;
                $candidato['_sobrComparacion'] = $sobrA;
            }


            $mejor = $this->esMejor($candidato, $mejor) ? $candidato : $mejor;
        }

        if (!$mejor) return ['ok' => false, 'reason' => 'Sin producto base compatible (longitud)'];

        unset($mejor['_effComparacion'], $mejor['_sobrComparacion']);
        return ['ok' => true, 'sugerencia' => $mejor];
    }

    /* ---------------- Helpers ---------------- */

    /** Longitud de elemento en mm */
    /** Longitud de elemento en mm (acepta m, cm o mm; o número “pelado”) */
    private function lenElemMm(Elemento $e): int
    {
        // Soporta campos alternativos si los tuvieras
        if (!empty($e->longitud_mm)) return (int) round($e->longitud_mm);
        if (!empty($e->longitud_m))  return $this->parseLongitudToMm($e->longitud_m);

        // Campo genérico "longitud": puede venir en m/cm/mm o como número
        if (isset($e->longitud) && $e->longitud !== null && $e->longitud !== '') {
            return $this->parseLongitudToMm($e->longitud);
        }

        return 0;
    }

    /** Piezas necesarias del elemento (mejor esfuerzo) */
    private function piezasNecesarias(Elemento $e): int
    {
        foreach (['cantidad', 'piezas', 'unidades', 'uds', 'barras'] as $campo) {
            if (isset($e->{$campo}) && (int)$e->{$campo} > 0) return (int)$e->{$campo};
        }
        return 1;
    }


    /** Diámetro elemento en mm (normaliza strings como "16.00") */
    private function diamElemMm(Elemento $e): float
    {
        return $this->parseDiametroToMm($e->diametro ?? null);
    }

    /** Diámetro producto base en mm (normaliza) */
    private function diamPBmm(ProductoBase $pb): float
    {
        // en tu modelo PB guardas 'diametro' como número nominal (mm)
        return $this->parseDiametroToMm($pb->diametro ?? null);
    }

    /** Compatibilidad por diámetro con tolerancia */
    private function diametrosCompatibles(float $d1mm, float $d2mm): bool
    {
        if ($d1mm <= 0 || $d2mm <= 0) return false;
        return abs($d1mm - $d2mm) <= self::DIAM_TOL_MM;
    }

    /** Normaliza un diámetro que puede venir como "16", "16.00", "16 mm" */
    private function parseDiametroToMm($raw): float
    {
        if ($raw === null || $raw === '') return 0.0;
        $str = is_string($raw) ? trim(mb_strtolower((string)$raw)) : $raw;

        if (is_numeric($str)) return (float)$str;

        // extrae número con coma o punto
        $num = null;
        if (preg_match('/([\d]+[.,]?\d*)/', str_replace(',', '.', (string)$str), $m)) {
            $num = (float) $m[1];
        }
        if ($num === null) return 0.0;

        // si incluye "mm" lo dejamos igual; si no, asumimos mm (tu negocio usa mm)
        return (float)$num;
    }

    private function longitudBarraMm(ProductoBase $pb): int
    {
        if (!empty($pb->longitud_mm))        return (int) round($pb->longitud_mm);
        if (!empty($pb->longitud_activa_mm)) return (int) round($pb->longitud_activa_mm);
        if (!empty($pb->longitud_m)) {
            $L = $this->parseLongitudToMm($pb->longitud_m);
            if ($L > 0) return $L;
        }
        if (isset($pb->longitud) && $pb->longitud !== null && $pb->longitud !== '') {
            $L = $this->parseLongitudToMm($pb->longitud);
            if ($L > 0) return $L;
        }
        return 0;
    }

    private function parseLongitudToMm($raw): int
    {
        if ($raw === null || $raw === '') return 0;
        $str = is_string($raw) ? trim(mb_strtolower((string)$raw)) : $raw;

        if (is_numeric($str)) {
            $v = (float) $str;
            if ($v < 100)   return (int) round($v * 1000); // m → mm
            if ($v >= 1000) return (int) round($v);        // ya mm
            return (int) round($v * 10);                   // cm → mm
        }

        $num = null;
        if (preg_match('/([\d]+[.,]?\d*)/', str_replace(',', '.', (string)$str), $m)) {
            $num = (float) $m[1];
        }
        if ($num === null) return 0;

        $hasMM   = (mb_strpos($str, 'mm') !== false);
        $hasMWord = (mb_strpos($str, ' metro') !== false) || preg_match('/(^|\s)m($|\s)/', $str);
        $hasCM   = (mb_strpos($str, 'cm') !== false);

        if ($hasMM)   return (int) round($num);
        if ($hasCM)   return (int) round($num * 10);
        if ($hasMWord) return (int) round($num * 1000);

        if ($num < 100)   return (int) round($num * 1000);
        if ($num >= 1000) return (int) round($num);
        return (int) round($num * 10);
    }

    private function mejorPareja(int $lenA, Collection $colegas, int $L): ?array
    {
        $best = null;
        foreach ($colegas as $b) {
            $lenB = $this->lenElemMm($b);
            if ($lenB <= 0) continue;

            $patron = $lenA + $lenB;
            if ($patron > $L) continue;

            $nPatrones = intdiv($L, $patron);      // nº de parejas A+B por barra
            $uso       = $nPatrones * $patron;
            $sobr      = $L - $uso;
            $eff       = $L > 0 ? ($uso / $L) : 0;

            if (!$best || $sobr < $best['sobr']) {
                $best = [
                    'idB'   => $b->id,
                    'lenB'  => $lenB,
                    'nA'    => $nPatrones,   // A por barra
                    'nB'    => $nPatrones,   // B por barra
                    'uso'   => $uso,
                    'sobr'  => $sobr,
                    'eff'   => $eff,
                ];
                if ($sobr === 0) break; // encaje perfecto
            }
        }
        return $best;
    }


    private function esMejor(array $cand, ?array $best): bool
    {
        if (!$best) return true;
        $effC = $cand['_effComparacion'] ?? 0;
        $effB = $best['_effComparacion'] ?? 0;
        if ($effC === $effB) {
            $sobC = $cand['_sobrComparacion'] ?? PHP_INT_MAX;
            $sobB = $best['_sobrComparacion'] ?? PHP_INT_MAX;
            if ($sobC === $sobB) {
                return ($cand['barras_totales'] ?? PHP_INT_MAX) < ($best['barras_totales'] ?? PHP_INT_MAX);
            }
            return $sobC < $sobB;
        }
        return $effC > $effB;
    }
}
