<?php

namespace App\Services;

use App\Models\Elemento;
use App\Models\ProductoBase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SugeridorProductoBaseService
{
    /** Tolerancia de diámetro en mm */
    private const DIAM_TOL_MM = 0.1;

    /** Máximo de piezas por patrón (configurable) */
    private const MAX_PIEZAS_POR_PATRON = 4; // incluye AAAB, AABB, etc.

    /** Máximo de tipos distintos en patrón (A, A+B, A+B+C) */
    private const MAX_TIPOS_EN_PATRON = 3;

    /**
     * Punto de entrada:
     * - $elemento: A
     * - $productosBaseBarra: colección de PB tipo "barra" (posibles longitudes activas)
     * - $colegasOrdenados: A y colegas de esta planilla y las 2 siguientes, ya ordenados por prioridad
     */
    public function sugerirParaElemento(Elemento $elemento, Collection $productosBaseBarra, Collection $colegasOrdenados): array
    {
        // 1) Normalizaciones básicas
        $diamA_mm = $this->diamElemMm($elemento);
        if ($diamA_mm <= 0.0) {
            return ['ok' => false, 'reason' => 'Diámetro de elemento inválido'];
        }

        $lenA_cm = $this->lenElementoCm($elemento); // ← SIEMPRE VIENE EN CM
        if ($lenA_cm <= 0) {
            return ['ok' => false, 'reason' => 'Longitud de elemento inválida'];
        }

        $piezasA_necesarias = $this->piezasNecesarias($elemento);

        // 2) Filtrar PB por diámetro
        $pbsCompatibles = $productosBaseBarra->filter(function (ProductoBase $pb) use ($diamA_mm) {
            $d = $this->diamPBmm($pb);
            return $d > 0 && $this->diametrosCompatibles($diamA_mm, $d);
        })->values();

        if ($pbsCompatibles->isEmpty()) {
            return ['ok' => false, 'reason' => 'Sin producto base compatible por diámetro'];
        }

        // 3) Construir pool de candidatos (A + colegas) con mismo diámetro
        $pool = [];
        $addCandidato = function ($e) use (&$pool, $diamA_mm) {
            $d = $this->diamElemMm($e);
            if ($d <= 0 || !$this->diametrosCompatibles($diamA_mm, $d)) return;
            $len_cm = $this->lenElementoCm($e);
            if ($len_cm <= 0) return;

            $pool[$e->id] = [
                'elemento_id' => $e->id,
                'len_cm'      => $len_cm,
                'diam_mm'     => $d,
                'piezas'      => $this->piezasNecesarias($e),
            ];
        };

        $addCandidato($elemento); // A primero
        foreach ($colegasOrdenados as $e) {
            if ($e->id === $elemento->id) continue;
            $addCandidato($e);
        }

        // Si solo está A en el pool, igual seguimos; el algoritmo contempla AA, AAA, etc.
        if (empty($pool)) {
            return ['ok' => false, 'reason' => 'No hay elementos compatibles por diámetro'];
        }

        // 4) Evaluar cada PB y elegir el mejor
        $mejor = null;

        foreach ($pbsCompatibles as $pb) {
            $L_cm = $this->longitudBarraCm($pb);
            if ($L_cm <= 0 || $L_cm < min($lenA_cm, 10)) continue; // descarte básico

            $patron = $this->encontrarMejorPatronParaBarra(
                L_cm: $L_cm,
                pool: $pool,
                elementoA_id: $elemento->id
            );

            if (!$patron) continue;

            // Barras necesarias para cubrir A (mínimo)
            $piezasA_por_barra = $patron['piezas_por_barra'][$elemento->id] ?? 0;
            $barras_totales = $piezasA_por_barra > 0
                ? (int) ceil($piezasA_necesarias / $piezasA_por_barra)
                : $piezasA_necesarias;

            $candidato = [
                'producto_base_id'   => $pb->id,
                'diametro_mm'        => $this->diamPBmm($pb),
                'longitud_barra_cm'  => $L_cm,
                'patron'             => $patron['patron'],           // lista de items del patrón
                'repeticiones'       => $patron['repeticiones'],      // veces que cabe el patrón por barra
                'piezas_por_barra'   => $patron['piezas_por_barra'],  // por elemento_id
                'uso_cm'             => $patron['uso_cm'],
                'sobrante_cm'        => $patron['sobrante_cm'],
                'eficiencia'         => $patron['eficiencia'],
                'piezasA_por_barra'  => $piezasA_por_barra,
                'barras_totales'     => max($barras_totales, 1),
                // métricas para desempate
                '_clave'             => [
                    // Orden: sobrante asc, eficiencia desc, piezas_totales asc, sobrante asc (refuerzo), barras_totales asc
                    'sobr'   => $patron['sobrante_cm'],
                    'eff'    => $patron['eficiencia'],
                    'cuts'   => $patron['piezas_totales_por_barra'],
                    'sobr2'  => $patron['sobrante_cm'],
                    'bars'   => max($barras_totales, 1),
                ],
            ];

            // dentro del foreach de PBs:
            $ctx = [
                'A'   => $elemento->id,
                'pb'  => $pb->id,
                'L'   => $L_cm ?? null,
                'run' => spl_object_id($elemento), // o un contador propio
            ];
            $mejor = $this->elegirMejor($mejor, $candidato, $ctx);
        }

        if (!$mejor) {
            return ['ok' => false, 'reason' => 'Sin patrón compatible por longitud'];
        }

        unset($mejor['_clave']);
        return ['ok' => true, 'sugerencia' => $mejor];
    }

    /* ============ Núcleo de búsqueda de patrón por barra ============ */

    /**
     * Busca la combinación (no ordenada) que:
     *  - quepa en L_cm,
     *  - incluya ≥1 A,
     *  - hasta MAX_TIPOS_EN_PATRON tipos distintos,
     *  - hasta MAX_PIEZAS_POR_PATRON piezas totales por patrón,
     *  - minimice el sobrante por barra.
     *
     * Retorna estructura con: patrón elegido, repeticiones por barra, piezas_por_barra, uso/sobrante/eficiencia.
     */
    private function encontrarMejorPatronParaBarra(int $L_cm, array $pool, int $elementoA_id): ?array
    {
        // Lista base de candidatos (id, len_cm)
        $items = array_values(array_map(fn($x) => [
            'elemento_id' => $x['elemento_id'],
            'len_cm'      => $x['len_cm'],
        ], $pool));

        // ordenar por longitud asc para podar antes
        usort($items, fn($a, $b) => $a['len_cm'] <=> $b['len_cm']);

        $mejor = null;

        // Generador de combinaciones no ordenadas con repetición
        $n = count($items);
        $maxTipos = self::MAX_TIPOS_EN_PATRON;
        $maxPiezas = self::MAX_PIEZAS_POR_PATRON;

        // estrategia: backtracking con índices no decrecientes
        $stack = []; // cada entrada: ['idx' => i, 'elemento_id' => .., 'len_cm' => ..]
        $this->backtrackPatrones(
            items: $items,
            startIdx: 0,
            L_cm: $L_cm,
            reqElementoId: $elementoA_id,
            maxTipos: $maxTipos,
            maxPiezas: $maxPiezas,
            actual: [],
            mejor: $mejor // por referencia
        );

        return $mejor;
    }

    private function backtrackPatrones(
        array $items,
        int $startIdx,
        int $L_cm,
        int $reqElementoId,
        int $maxTipos,
        int $maxPiezas,
        array $actual,
        ?array &$mejor
    ): void {
        // Evaluar combinación actual si no está vacía
        if (!empty($actual)) {
            // Debe contener al menos una A
            $contieneA = false;
            $tipos = [];
            $total_cm = 0;
            foreach ($actual as $it) {
                $total_cm += $it['len_cm'];
                $tipos[$it['elemento_id']] = true;
                if ($it['elemento_id'] === $reqElementoId) $contieneA = true;
            }

            if ($contieneA && $total_cm <= $L_cm && count($tipos) <= $maxTipos) {
                // Patron repetido por barra
                $reps = intdiv($L_cm, $total_cm);
                if ($reps > 0) {
                    $uso = $reps * $total_cm;
                    $sobr = $L_cm - $uso;
                    $eff = $L_cm > 0 ? $uso / $L_cm : 0;

                    // piezas por barra (agregadas por reps)
                    $piezasPorBarra = [];
                    foreach ($actual as $it) {
                        $piezasPorBarra[$it['elemento_id']] = ($piezasPorBarra[$it['elemento_id']] ?? 0) + 1;
                    }
                    foreach ($piezasPorBarra as $k => $v) {
                        $piezasPorBarra[$k] = $v * $reps;
                    }

                    $candidato = [
                        'patron' => $this->compactarPatron($actual), // [{elemento_id,len_cm,count_por_patron},...]
                        'repeticiones' => $reps,
                        'piezas_por_barra' => $piezasPorBarra,
                        'piezas_totales_por_barra' => array_sum($piezasPorBarra),
                        'uso_cm' => $uso,
                        'sobrante_cm' => $sobr,
                        'eficiencia' => $eff,
                    ];

                    $mejor = $this->mejorPatron($mejor, $candidato);
                }
            }
        }

        // Si alcanzamos el tope de piezas, parar
        if (count($actual) >= $maxPiezas) return;

        // Intentar añadir más piezas manteniendo no-decreciente
        for ($i = $startIdx; $i < count($items); $i++) {
            $nuevo = $items[$i];

            // poda rápida: si ya nos pasamos de L con una sola repetición del patrón extendido, aún podría servir con reps>=1,
            // pero evaluamos cuando toque. Aquí solo evitamos combinaciones absurdas muy largas.
            $actualNuevo = $actual;
            $actualNuevo[] = $nuevo;

            // Podar tipos si excede maxTipos
            $tipos = [];
            foreach ($actualNuevo as $it) $tipos[$it['elemento_id']] = true;
            if (count($tipos) > $maxTipos) continue;

            $this->backtrackPatrones(
                items: $items,
                startIdx: $i, // permitir repetición (combinaciones con repetición)
                L_cm: $L_cm,
                reqElementoId: $reqElementoId,
                maxTipos: $maxTipos,
                maxPiezas: $maxPiezas,
                actual: $actualNuevo,
                mejor: $mejor
            );
        }
    }

    private function mejorPatron(?array $best, array $cand): array
    {
        if (!$best) return $cand;

        // Orden: sobrante asc, eficiencia desc, cortes asc, sobrante asc (refuerzo), piezasA_por_barra desc
        $cmp = $cand['sobrante_cm'] <=> $best['sobrante_cm'];
        if ($cmp !== 0) return $cmp < 0 ? $cand : $best;

        $cmp = $best['eficiencia'] <=> $cand['eficiencia']; // desc
        if ($cmp !== 0) return $cmp < 0 ? $cand : $best;

        $cmp = $cand['piezas_totales_por_barra'] <=> $best['piezas_totales_por_barra']; // menos cortes
        if ($cmp !== 0) return $cmp < 0 ? $cand : $best;

        $cmp = $cand['uso_cm'] <=> $best['uso_cm']; // más uso
        if ($cmp !== 0) return $cmp > 0 ? $cand : $best;

        return $cand; // indiferente
    }

    private function compactarPatron(array $lista): array
    {
        // convierte [A,B,A] → [{A,count:2,len:A_len}, {B,count:1,len:B_len}]
        $acc = [];
        foreach ($lista as $it) {
            $k = $it['elemento_id'];
            if (!isset($acc[$k])) {
                $acc[$k] = ['elemento_id' => $k, 'len_cm' => $it['len_cm'], 'count_por_patron' => 0];
            }
            $acc[$k]['count_por_patron']++;
        }
        return array_values($acc);
    }

    private function elegirMejor(?array $best, array $cand): array
    {

        if (!$best) return $cand;

        // mismo criterio que en patrón, con barra_totales como factor final
        $a = $cand['_clave'];
        $b = $best['_clave'];
        // sobrante asc
        if ($a['sobr'] !== $b['sobr']) return ($a['sobr'] < $b['sobr']) ? $cand : $best;
        // eficiencia desc
        if ($a['eff'] !== $b['eff'])   return ($a['eff'] > $b['eff']) ? $cand : $best;
        // cortes asc
        if ($a['cuts'] !== $b['cuts']) return ($a['cuts'] < $b['cuts']) ? $cand : $best;
        // sobrante asc (refuerzo)
        if ($a['sobr2'] !== $b['sobr2']) return ($a['sobr2'] < $b['sobr2']) ? $cand : $best;
        // barras totales asc
        if ($a['bars'] !== $b['bars']) return ($a['bars'] < $b['bars']) ? $cand : $best;


        return $cand;
    }

    /* ========================== Helpers ========================== */

    /** Longitud elemento en cm (tu BD trae cm) */
    private function lenElementoCm(Elemento $e): int
    {
        // si tienes otros campos, respétalos, pero por defecto cm:
        if (isset($e->longitud) && $e->longitud !== null && $e->longitud !== '') {
            // viene en cm → forzamos entero cm
            $num = $this->parseNumero($e->longitud);
            return (int) round($num);
        }
        return 0;
    }

    /** Longitud PB en cm (PB trae metros) */
    private function longitudBarraCm(ProductoBase $pb): int
    {
        // prioridad: longitud_activa_m > longitud_m > longitud (si indica m)
        foreach (['longitud_activa_m', 'longitud_m', 'longitud'] as $campo) {
            if (!isset($pb->{$campo}) || $pb->{$campo} === null || $pb->{$campo} === '') continue;
            $m = $this->parseNumero($pb->{$campo});
            if ($m > 0 && $m < 1000) { // m razonables
                return (int) round($m * 100);
            }
        }
        // fallback: si hubiera mm
        if (!empty($pb->longitud_mm)) return (int) round($pb->longitud_mm / 10.0);
        return 0;
    }

    /** Piezas necesarias del elemento */
    private function piezasNecesarias(Elemento $e): int
    {
        foreach (['cantidad', 'piezas', 'unidades', 'uds', 'barras'] as $campo) {
            if (isset($e->{$campo}) && (int)$e->{$campo} > 0) return (int)$e->{$campo};
        }
        return 1;
    }

    /** Diámetro elemento en mm */
    private function diamElemMm(Elemento $e): float
    {
        return $this->parseDiametroMm($e->diametro ?? null);
    }

    /** Diámetro PB en mm */
    private function diamPBmm(ProductoBase $pb): float
    {
        return $this->parseDiametroMm($pb->diametro ?? null);
    }

    /** Compatibilidad de diámetro con tolerancia */
    private function diametrosCompatibles(float $d1mm, float $d2mm): bool
    {
        return ($d1mm > 0 && $d2mm > 0 && abs($d1mm - $d2mm) <= self::DIAM_TOL_MM);
    }

    private function parseDiametroMm($raw): float
    {
        if ($raw === null || $raw === '') return 0.0;
        if (is_numeric($raw)) return (float)$raw;
        $s = mb_strtolower(trim((string)$raw));
        $s = str_replace(',', '.', $s);
        if (preg_match('/([\d]+(?:\.\d+)?)/', $s, $m)) {
            return (float)$m[1]; // asumimos mm nominal
        }
        return 0.0;
    }

    private function parseNumero($raw): float
    {
        if ($raw === null || $raw === '') return 0.0;
        if (is_numeric($raw)) return (float)$raw;
        $s = str_replace(',', '.', (string)$raw);
        if (preg_match('/([\d]+(?:\.\d+)?)/', $s, $m)) {
            return (float)$m[1];
        }
        return 0.0;
    }
}
