<?php

namespace App\Services;

/**
 * Servicio: traduce elementos a BVBS (BF2D) para máquinas PROGRESS MSR20.
 * Formato compatible con Ferrawin/Progress.
 *
 * Entradas por elemento (array):
 *  - marca (string)            -> H:p
 *  - barras (int>0)            -> H:n
 *  - diametro (int mm>0)       -> H:d
 *  - dimensiones (string cm)   -> G:l(mm)/w(°)
 *  - longitud (float cm)       -> H:l (longitud total en mm)
 *  - peso (float kg total)     -> H:e = peso_por_barra
 *  - calidad (string)          -> H:g (p.ej. B500SD)
 *  - proyecto, plano, indice   -> H:j/r/i (opcionales)
 */
class ProgressBVBSService
{
    /** Exporta varios elementos. Devuelve texto BVBS con CRLF. */
    public function exportarLote(array $elementos): string
    {
        $lineas = array_map(fn($e) => $this->exportarElemento($e), $elementos);
        return implode("\r\n", $lineas) . "\r\n";
    }

    /** Exporta un elemento a una línea BF2D con checksum. */
    public function exportarElemento(array $e): string
    {
        $this->validar($e);

        $marca  = trim((string)($e['marca'] ?? ''));
        $barras = (int)($e['barras'] ?? 0);
        $diam   = (int)($e['diametro'] ?? 0);

        // Calcular longitud total en mm
        $longitudMm = $this->calcularLongitudTotal($e);

        // Peso por barra con coma decimal (formato europeo)
        $pesoPorBarra = isset($e['peso']) && $barras > 0
            ? str_replace('.', ',', number_format((float)$e['peso'] / $barras, 3, '.', ''))
            : '0';

        // Header H (sin @ después de H)
        $h = 'H';
        if (!empty($e['proyecto'])) $h .= 'j' . $this->txt($e['proyecto']) . '@';
        if (!empty($e['plano']))    $h .= 'r' . $this->txt($e['plano']) . '@';
        if ($marca !== '')          $h .= "p{$marca}@";
        $h .= "l{$longitudMm}@";
        $h .= "n{$barras}@";
        $h .= "e{$pesoPorBarra}@";
        $h .= "d{$diam}@";
        if (!empty($e['calidad']))  $h .= 'g' . $this->txt($e['calidad']) . '@';
        $h .= 'v@'; // Campo vacío requerido por Progress

        // Geometría G (sin @ después de G)
        $pares = $this->parsearDimensionesCm((string)($e['dimensiones'] ?? ''));
        $g = '';
        if ($pares) {
            $g = 'G';
            foreach ($pares as [$lmm, $ang]) {
                $g .= "l{$lmm}@w{$ang}@";
            }
            // NO añadir w0@ extra, ya está incluido en el último par
        } elseif (!empty($e['longitud'])) {
            // Sin geometría, solo longitud recta
            $g = 'G';
            $g .= 'l' . (int)round(((float)$e['longitud']) * 10) . '@w0@';
        }

        $linea = 'BF2D@' . $h . $g;

        // Checksum tipo C (suma ASCII mod 100)
        $linea .= $this->checksumC($linea);

        return $linea;
    }

    /** Valida mínimos: barras, diametro y G o longitud. */
    private function validar(array $e): void
    {
        if (empty($e['barras']) || (int)$e['barras'] <= 0) {
            throw new \InvalidArgumentException('barras > 0 requerido');
        }
        if (empty($e['diametro']) || (int)$e['diametro'] <= 0) {
            throw new \InvalidArgumentException('diametro mm requerido');
        }

        $tieneG = !empty($this->parsearDimensionesCm((string)($e['dimensiones'] ?? '')));
        $tieneL = !empty($e['longitud']);
        if (!$tieneG && !$tieneL) {
            throw new \InvalidArgumentException('dimensiones (cm) o longitud total (cm) requerida');
        }
    }

    /**
     * Calcula la longitud total en mm desde dimensiones o longitud.
     */
    private function calcularLongitudTotal(array $e): int
    {
        // Si hay dimensiones, sumar todas las longitudes
        $pares = $this->parsearDimensionesCm((string)($e['dimensiones'] ?? ''));
        if ($pares) {
            $total = 0;
            foreach ($pares as [$lmm, $ang]) {
                $total += $lmm;
            }
            return $total;
        }

        // Si no, usar longitud directa (cm -> mm)
        if (!empty($e['longitud'])) {
            return (int)round(((float)$e['longitud']) * 10);
        }

        return 0;
    }

    /**
     * Parser de "dimensiones" en cm.
     * Acepta formatos: "40 90d 25 90d" o "90d 40 90d 25"
     * Devuelve pares [longitud_mm, angulo_deg].
     * El último segmento siempre tiene ángulo 0.
     */
    private function parsearDimensionesCm(string $dim): array
    {
        $dim = trim(preg_replace('/\s+/', ' ', $dim));
        if ($dim === '') return [];
        $tokens = explode(' ', $dim);

        $pares = [];
        $pendLen = null;
        $pendAng = null;

        foreach ($tokens as $tk) {
            // Detectar ángulo (soporta negativos: -90d, 90d, 90D)
            if (preg_match('/^(-?\d+)[dD]$/', $tk, $m)) {
                $ang = (int)$m[1];
                if ($pendLen !== null) {
                    $pares[] = [(int)round($pendLen * 10), $ang];
                    $pendLen = null;
                } else {
                    $pendAng = $ang;
                }
            } elseif (preg_match('/^\d+(?:[.,]\d+)?$/', $tk)) {
                $len = (float)str_replace(',', '.', $tk);
                if ($pendAng !== null) {
                    $pares[] = [(int)round($len * 10), $pendAng];
                    $pendAng = null;
                } else {
                    $pendLen = $len;
                }
            }
        }

        // Último segmento pendiente -> ángulo 0
        if ($pendLen !== null) {
            $pares[] = [(int)round($pendLen * 10), 0];
        }

        return $pares;
    }

    /** Checksum tipo C: 'C' + (suma ASCII mod 100) + '@' */
    private function checksumC(string $cadena): string
    {
        $suma = 0;
        for ($i = 0, $L = strlen($cadena); $i < $L; $i++) {
            $suma += ord($cadena[$i]);
        }
        return 'C' . str_pad($suma % 100, 2, '0', STR_PAD_LEFT) . '@';
    }

    /** Limpia texto para campos H. */
    private function txt(string $t): string
    {
        return trim(str_replace(["\r", "\n", '@'], ' ', $t));
    }
}
