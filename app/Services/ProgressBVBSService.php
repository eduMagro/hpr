<?php

namespace App\Services;

/**
 * Servicio: traduce elementos a BVBS (BF2D) para máquinas PROGRESS.
 * Entradas por elemento (array):
 *  - marca (string)            -> H:p
 *  - barras (int>0)            -> H:n
 *  - diametro (int mm>0)       -> H:d
 *  - dimensiones (string cm)   -> G:l(mm)/w(°) desde patrones "40 90d 25 90d" o "90d 40 90d"
 *  - longitud (float cm)       -> H:l si NO hay G
 *  - peso (float kg total)     -> H:e = peso_por_barra
 *  - mandril_mm (int)          -> H:s (si falta, s0 = automático)
 *  - calidad (string)          -> H:g   (p.ej. 500S)
 *  - capa (string)             -> H:a   (p.ej. L1 o Q1)
 *  - PLANILLA/Proyecto, MARCA/Plano, indice   -> H:j/r/i (opcionales)
 *  - box (int)                 -> PfN (caja forzada, opcional)
 */
class ProgressBVBSService
{
    public function __construct(
        private readonly string $modoChecksum = 'ip', // 'ip' | 'none'
        private readonly bool   $mandrilAuto   = true // s0 si no se provee mandril_mm
    ) {}

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

        // Header H
        $h = 'H@';
        if (!empty($e['proyecto'])) $h .= 'j' . $this->txt($e['proyecto']) . '@';
        if (!empty($e['plano']))    $h .= 'r' . $this->txt($e['plano']) . '@';
        if (!empty($e['indice']))   $h .= 'i' . $this->txt($e['indice']) . '@';

        $marca  = trim((string)($e['marca']   ?? ''));
        $barras = (int)($e['barras']         ?? 0);
        $diam   = (int)($e['diametro']       ?? 0);
        if ($marca !== '') $h .= "p{$marca}@";
        $h .= "n{$barras}@d{$diam}@";

        if (!empty($e['calidad'])) $h .= 'g' . $this->txt($e['calidad']) . '@';
        if (isset($e['peso']) && $barras > 0) {
            $h .= 'e' . round((float)$e['peso'] / $barras, 3) . '@';
        }
        if (!empty($e['mandril_mm'])) $h .= 's' . (int)$e['mandril_mm'] . '@';
        elseif ($this->mandrilAuto)   $h .= 's0@';
        if (!empty($e['capa']))       $h .= 'a' . $this->txt($e['capa']) . '@';

        // Geometría G (desde dimensiones en cm). Si no hay G, usa H:l (cm→mm).
        $pares = $this->parsearDimensionesCm((string)($e['dimensiones'] ?? ''));
        $linea = 'BF2D@' . $h;
        if ($pares) {
            $g = 'G@';
            foreach ($pares as [$lmm, $ang]) {
                $g .= "l{$lmm}@w{$ang}@";
            }
            $g .= 'w0@';
            $linea .= $g;
        } elseif (!empty($e['longitud'])) {
            $linea .= 'Hl' . (int)round(((float)$e['longitud']) * 10) . '@';
        }

        // Bloque privado: box opcional (@PfN@)
        if (!empty($e['box'])) $linea .= 'Pf' . (int)$e['box'] . '@';

        // Checksum
        if ($this->modoChecksum === 'ip') $linea .= $this->checksumIP($linea);
        return $linea;
    }

    /** Valida mínimos: barras, diametro y G o longitud. */
    private function validar(array $e): void
    {
        if (empty($e['barras'])   || (int)$e['barras']   <= 0) throw new \InvalidArgumentException('barras > 0 requerido');
        if (empty($e['diametro']) || (int)$e['diametro'] <= 0) throw new \InvalidArgumentException('diametro mm requerido');

        $tieneG = !empty($this->parsearDimensionesCm((string)($e['dimensiones'] ?? '')));
        $tieneL = !empty($e['longitud']);
        if (!$tieneG && !$tieneL) throw new \InvalidArgumentException('dimensiones (cm) o longitud total (cm) requerida');
    }

    /**
     * Parser robusto de "dimensiones" en cm.
     * Acepta orden longitud-ángulo: "40 90d 25 90d"
     * y orden ángulo-longitud:      "90d 40 90d 25"
     * Devuelve pares [longitud_mm, angulo_deg].
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
            if (preg_match('/^(\d+)[dD]$/', $tk, $m)) {
                $ang = (int)$m[1];
                if ($pendLen !== null) {
                    $pares[] = [(int)round($pendLen * 10), $ang];
                    $pendLen = null;
                } else $pendAng = $ang;
            } elseif (preg_match('/^\d+(?:[.,]\d+)?$/', $tk)) {
                $len = (float)str_replace(',', '.', $tk);
                if ($pendAng !== null) {
                    $pares[] = [(int)round($len * 10), $pendAng];
                    $pendAng = null;
                } else $pendLen = $len;
            }
        }
        if ($pendLen !== null) $pares[] = [(int)round($pendLen * 10), 0];
        return $pares;
    }

    /** Checksum tipo IP: 'IP' . chr(96 - (Σ ASCII % 32)). */
    private function checksumIP(string $cadena): string
    {
        $suma = 0;
        for ($i = 0, $L = strlen($cadena); $i < $L; $i++) $suma += ord($cadena[$i]);
        return 'IP' . chr(96 - ($suma % 32));
    }

    /** Limpia texto para campos H. */
    private function txt(string $t): string
    {
        return trim(str_replace(["\r", "\n", '@'], ' ', $t));
    }
}
