<?php

namespace App\Services;

use App\Models\Elemento;
use App\Models\ProductoBase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SugeridorProductoBaseService
{
    /**
     * Sugerir el mejor ProductoBase para un elemento (con posible pareja).
     *
     * @param  Elemento     $elemento
     * @param  Collection   $productosBaseCompatibles  // colección de ProductoBase ya filtrados a tipo "barra" y longitudes activas
     * @param  Collection   $colegasPlanilla           // elementos de la misma planilla para emparejar
     * @return array { producto_base_id, longitud_barra_mm, n_por_barra, sobrante_mm, eficiencia,
     *                barras_totales, pareja?: { elemento_id, n_por_barra, sobrante_mm, eficiencia } }
     */
    public function sugerirParaElemento(Elemento $elemento, Collection $productosBaseCompatibles, Collection $colegasPlanilla): array
    {
        $lenA = $this->lenElemMm($elemento);
        if ($lenA <= 0) return ['ok' => false, 'reason' => 'Longitud no válida'];

        // filtra colegas compatibles (mismo diametro o regla que uses)
        $colegas = $colegasPlanilla->filter(function ($e) use ($elemento) {
            if ($e->id === $elemento->id) return false;
            // regla simple: mismo diametro (ajusta a tu negocio: tolerancia, rango, etc.)
            return (float)$e->diametro == (float)$elemento->diametro;
        })->values();

        $mejor = null;

        foreach ($productosBaseCompatibles as $pb) {
            $L = $this->longitudBarraMm($pb);
            Log::debug('[SugeridorPB][calc]', [
                'pb_id'     => $pb->id,
                'tipo'      => $pb->tipo,
                'diametro'  => $pb->diametro,
                'long_raw'  => $pb->longitud ?? null,
                'long_m'    => $pb->longitud_m ?? null, // <- el accesor “12 metros”
                'L_mm'      => $L,
            ]);
            if ($L <= 0) continue;


            // 1) Packing simple: solo el elemento A
            $nA = intdiv($L, $lenA);
            $usoA = $nA * $lenA;
            $sobrA = $L - $usoA;
            $effA = $usoA / $L; // 0..1

            // Estimar nº de barras totales para cubrir A->barras
            $barrasTotalesA = $nA > 0 ? (int)ceil(($elemento->barras ?? 1) / $nA) : ($elemento->barras ?? 1);

            $candidato = [
                'producto_base_id'     => $pb->id,
                'longitud_barra_mm'    => $L,
                'n_por_barra'          => max($nA, 0),
                'sobrante_mm'          => $sobrA,
                'eficiencia'           => $effA,
                'barras_totales'       => max($barrasTotalesA, 1),
                'pareja'               => null,
            ];

            // 2) Intento de emparejamiento con un solo colega (mejor pareja)
            $mejorPareja = $this->mejorPareja($lenA, $colegas, $L);
            if ($mejorPareja) {
                $nA2 = 1; // 1 corte de A
                $nB2 = 1; // 1 corte de B (por pareja básica)
                $usoAB = $lenA + $mejorPareja['lenB'];
                $sobrAB = $L - $usoAB;
                $effAB = $usoAB / $L;
                // Nota: podrías también calcular n_por_barra con múltiplos (p.ej. 2A+1B si cupiera),
                // aquí mantenemos simple: 1A + 1B como pareja por barra
                $candidato['pareja'] = [
                    'elemento_id'   => $mejorPareja['idB'],
                    'lenB_mm'       => $mejorPareja['lenB'],
                    'n_por_barra'   => ['A' => $nA2, 'B' => $nB2],
                    'sobrante_mm'   => $sobrAB,
                    'eficiencia'    => $effAB,
                ];

                // Si la eficiencia con pareja es mejor, usamos esos datos para comparar
                if ($effAB > $effA) {
                    // Para comparación global, considera la eficiencia con pareja
                    $candidato['_effComparacion'] = $effAB;
                    $candidato['_sobrComparacion'] = $sobrAB;
                } else {
                    $candidato['_effComparacion'] = $effA;
                    $candidato['_sobrComparacion'] = $sobrA;
                }
            } else {
                $candidato['_effComparacion'] = $effA;
                $candidato['_sobrComparacion'] = $sobrA;
            }

            $mejor = $this->esMejor($candidato, $mejor) ? $candidato : $mejor;
        }

        if (!$mejor) return ['ok' => false, 'reason' => 'Sin producto base compatible'];

        // Limpia claves internas
        unset($mejor['_effComparacion'], $mejor['_sobrComparacion']);
        return ['ok' => true, 'sugerencia' => $mejor];
    }

    /* ---------------- Helpers ---------------- */

    private function lenElemMm(Elemento $e): int
    {
        // ajusta según tus columnas reales (longitud puede venir en cm/mm/m)
        // memoria: usas $e->longitud, $e->diametro; también tienes derivados peso_kg, diametro_mm, longitud_cm
        // if (!empty($e->longitud_cm)) return (int) round($e->longitud_cm * 10);   // cm -> mm
        if (!empty($e->longitud))   return (int) round((float)$e->longitud);     // ya mm (si así lo guardas)
        return 0;
    }

    private function longitudBarraMm(ProductoBase $pb): int
    {
        // 1) Si existen campos numéricos directos, priorízalos
        if (!empty($pb->longitud_mm))        return (int) round($pb->longitud_mm);
        if (!empty($pb->longitud_activa_mm)) return (int) round($pb->longitud_activa_mm);
        if (!empty($pb->longitud_m)) {
            // OJO: longitud_m es un ACCESOR tipo "12 metros"
            $L = $this->parseLongitudToMm($pb->longitud_m);
            if ($L > 0) return $L;
        }

        // 2) Tu caso real: columna 'longitud' numérica (12, 14 → metros)
        if (isset($pb->longitud) && $pb->longitud !== null && $pb->longitud !== '') {
            $L = $this->parseLongitudToMm($pb->longitud);
            if ($L > 0) return $L;
        }

        return 0;
    }

    /**
     * Acepta:
     *  - numérico: 12, 14, 12000, 14000
     *  - string: "12", "12.0", "12,0", "12 m", "12 metros", "12000 mm", "1200 cm"
     *  Reglas:
     *   - 'metros' o ' m ' → m → mm
     *   - 'mm' → ya mm
     *   - 'cm' → cm → mm
     *   - sin unidad:
     *       * valor < 100  → metros
     *       * valor >=1000 → milímetros
     *       * 100..999     → cm (raro, pero cubrimos)
     */
    private function parseLongitudToMm($raw): int
    {
        if ($raw === null || $raw === '') return 0;

        // normaliza
        $str = is_string($raw) ? trim(mb_strtolower((string)$raw)) : $raw;

        // si viene ya numérico puro
        if (is_numeric($str)) {
            $v = (float) $str;
            if ($v < 100)   return (int) round($v * 1000); // m → mm
            if ($v >= 1000) return (int) round($v);        // ya mm
            return (int) round($v * 10);                   // cm → mm (caso raro)
        }

        // string con texto/unidades
        // reemplaza coma por punto y extrae primer número (adm. 12, 12.0, 12,0)
        $num = null;
        if (preg_match('/([\d]+[.,]?\d*)/', str_replace(',', '.', (string)$str), $m)) {
            $num = (float) $m[1];
        }
        if ($num === null) return 0;

        $hasMM = (mb_strpos($str, 'mm') !== false);
        // cuidado: ' m ' en medio vs 'mm'
        $hasMWord = (mb_strpos($str, ' metro') !== false) || preg_match('/(^|\s)m($|\s)/', $str);
        $hasCM = (mb_strpos($str, 'cm') !== false);

        if ($hasMM)   return (int) round($num);           // ya mm
        if ($hasCM)   return (int) round($num * 10);      // cm → mm
        if ($hasMWord) return (int) round($num * 1000);   // m → mm

        // sin unidad explícita → heurística
        if ($num < 100)    return (int) round($num * 1000); // m → mm
        if ($num >= 1000)  return (int) round($num);        // ya mm
        return (int) round($num * 10);                      // cm (raro)
    }


    private function mejorPareja(int $lenA, Collection $colegas, int $L): ?array
    {
        $best = null;
        foreach ($colegas as $b) {
            $lenB = $this->lenElemMm($b);
            if ($lenB <= 0) continue;
            if ($lenA + $lenB > $L) continue;
            $sobr = $L - ($lenA + $lenB);
            if (!$best || $sobr < $best['sobr']) {
                $best = ['idB' => $b->id, 'lenB' => $lenB, 'sobr' => $sobr];
                if ($sobr === 0) break; // encaje perfecto
            }
        }
        return $best;
    }

    private function esMejor(array $cand, ?array $best): bool
    {
        if (!$best) return true;
        // comparar por eficiencia comparativa y luego por sobrante comparativo
        $effC = $cand['_effComparacion'] ?? 0;
        $effB = $best['_effComparacion'] ?? 0;
        if ($effC === $effB) {
            $sobC = $cand['_sobrComparacion'] ?? PHP_INT_MAX;
            $sobB = $best['_sobrComparacion'] ?? PHP_INT_MAX;
            if ($sobC === $sobB) {
                // desempate adicional: menos barras totales
                return ($cand['barras_totales'] ?? PHP_INT_MAX) < ($best['barras_totales'] ?? PHP_INT_MAX);
            }
            return $sobC < $sobB;
        }
        return $effC > $effB;
    }
}
