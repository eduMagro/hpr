<?php

namespace App\Helpers;

/**
 * Helper para convertir secuencias de doblado a representaciones SVG.
 *
 * Convierte datos de FerraWin (secuencia_doblado) en paths SVG para
 * visualizar la forma real de barras y estribos en etiquetas de ensamblaje.
 */
class SvgBarraHelper
{
    /**
     * Convierte una secuencia de doblado a un path SVG.
     *
     * @param array $secuencia Array de segmentos [{tipo: 'longitud'|'doblez', valor|angulo: float}, ...]
     * @param array $opciones [
     *   'maxWidth' => int,     // Ancho máximo del SVG (default: 200)
     *   'maxHeight' => int,    // Alto máximo del SVG (default: 60)
     *   'grosor' => int,       // Grosor de línea (default: 3)
     *   'color' => string,     // Color del trazo (default: '#000')
     * ]
     * @return array ['path' => string, 'width' => float, 'height' => float, 'viewBox' => string]
     */
    public static function secuenciaASvg(array $secuencia, array $opciones = []): array
    {
        $maxWidth = $opciones['maxWidth'] ?? 200;
        $maxHeight = $opciones['maxHeight'] ?? 60;
        $grosor = $opciones['grosor'] ?? 3;
        $color = $opciones['color'] ?? '#000';

        if (empty($secuencia)) {
            // Barra recta por defecto
            return [
                'path' => sprintf(
                    '<line x1="5" y1="30" x2="%d" y2="30" stroke="%s" stroke-width="%d" stroke-linecap="round"/>',
                    $maxWidth - 5,
                    $color,
                    $grosor
                ),
                'width' => $maxWidth,
                'height' => $maxHeight,
                'viewBox' => "0 0 {$maxWidth} {$maxHeight}",
            ];
        }

        // Calcular puntos del path
        $puntos = self::calcularPuntos($secuencia);

        if (empty($puntos)) {
            return self::secuenciaASvg([], $opciones);
        }

        // Calcular bounds
        $bounds = self::calcularBounds($puntos);

        // Calcular escala para ajustar al área disponible
        $margen = 10;
        $anchoDisponible = $maxWidth - (2 * $margen);
        $altoDisponible = $maxHeight - (2 * $margen);

        $escalaX = $anchoDisponible / max(1, $bounds['width']);
        $escalaY = $altoDisponible / max(1, $bounds['height']);
        $escala = min($escalaX, $escalaY, 1); // No ampliar, solo reducir

        // Aplicar escala y centrar
        $offsetX = $margen + ($anchoDisponible - $bounds['width'] * $escala) / 2 - $bounds['minX'] * $escala;
        $offsetY = $margen + ($altoDisponible - $bounds['height'] * $escala) / 2 - $bounds['minY'] * $escala;

        // Generar path
        $pathData = '';
        foreach ($puntos as $i => $punto) {
            $x = round($punto['x'] * $escala + $offsetX, 2);
            $y = round($punto['y'] * $escala + $offsetY, 2);
            $pathData .= ($i === 0 ? "M {$x} {$y}" : " L {$x} {$y}");
        }

        $path = sprintf(
            '<path d="%s" fill="none" stroke="%s" stroke-width="%d" stroke-linecap="round" stroke-linejoin="round"/>',
            $pathData,
            $color,
            $grosor
        );

        return [
            'path' => $path,
            'width' => $maxWidth,
            'height' => $maxHeight,
            'viewBox' => "0 0 {$maxWidth} {$maxHeight}",
        ];
    }

    /**
     * Renderiza un elemento (barra/estribo) completo con su forma y dimensiones.
     *
     * @param array $elemento Array con datos del elemento (secuencia_doblado, diametro, longitud)
     * @param string|null $cotas String de cotas visual (ej: "30 |_____130______|")
     * @param string $letra Letra identificadora (A, B, C...)
     * @param array $opciones Opciones de renderizado
     * @return string SVG completo del elemento
     */
    public static function renderizarForma(
        array $elemento,
        ?string $cotas = null,
        string $letra = 'A',
        array $opciones = []
    ): string {
        $x = $opciones['x'] ?? 0;
        $y = $opciones['y'] ?? 0;
        $maxWidth = $opciones['maxWidth'] ?? 350;
        $altura = $opciones['altura'] ?? 45;

        $secuencia = $elemento['secuencia_doblado'] ?? [];
        $diametro = $elemento['diametro'] ?? '?';
        $longitud = $elemento['longitud'] ?? 0;
        $tipo = $elemento['tipo'] ?? 'barra';

        // Área para el dibujo de la forma
        $anchoForma = 220;
        $altoForma = 35;

        // Generar SVG de la forma
        $svgData = self::secuenciaASvg($secuencia, [
            'maxWidth' => $anchoForma,
            'maxHeight' => $altoForma,
            'grosor' => 3,
        ]);

        // Construir el grupo SVG
        $svg = "<g transform=\"translate({$x}, {$y})\">";

        // Círculo con letra (para barras) o cuadrado (para estribos)
        if ($tipo === 'estribo' || strpos(strtolower($elemento['situacion'] ?? ''), 'estribo') !== false) {
            $svg .= "<rect x=\"0\" y=\"8\" width=\"24\" height=\"24\" fill=\"#fff\" stroke=\"#000\" stroke-width=\"2\" rx=\"3\"/>";
        } else {
            $svg .= "<circle cx=\"12\" cy=\"20\" r=\"12\" fill=\"#fff\" stroke=\"#000\" stroke-width=\"2\"/>";
        }
        $svg .= "<text x=\"12\" y=\"25\" text-anchor=\"middle\" font-size=\"14\" fill=\"#000\" font-weight=\"bold\">{$letra}</text>";

        // SVG de la forma
        $svg .= "<g transform=\"translate(35, 2)\">";
        $svg .= $svgData['path'];
        $svg .= "</g>";

        // Texto con dimensiones
        $textoX = 35 + $anchoForma + 10;
        $dimensionTexto = self::formatearDimensiones($elemento, $cotas);
        $svg .= "<text x=\"{$textoX}\" y=\"15\" font-size=\"10\" fill=\"#000\" font-weight=\"bold\">Ø{$diametro}</text>";
        $svg .= "<text x=\"{$textoX}\" y=\"30\" font-size=\"9\" fill=\"#333\">{$dimensionTexto}</text>";

        $svg .= "</g>";

        return $svg;
    }

    /**
     * Renderiza la sección completa de formas detalladas.
     *
     * @param array $composicion Array con barras y estribos
     * @param string|null $cotasEntidad Cotas de la entidad
     * @param array $armaduraConLetras Array con info de armadura y letras asignadas
     * @return string SVG de la sección completa
     */
    public static function renderizarSeccionFormas(
        array $composicion,
        ?string $cotasEntidad,
        array $armaduraConLetras,
        float $longitudTotal = 0
    ): string {
        $barras = $composicion['barras'] ?? [];
        $estribos = $composicion['estribos'] ?? [];

        $svg = "<g transform=\"translate(160, 5)\">";
        $svg .= "<text x=\"175\" y=\"14\" text-anchor=\"middle\" font-size=\"11\" font-weight=\"bold\" fill=\"#000\">FORMAS DE ARMADURA</text>";

        // Mostrar longitud total si existe
        if ($longitudTotal > 0) {
            $longitudTexto = number_format($longitudTotal, 2, '.', '') . 'm';
            $svg .= "<text x=\"175\" y=\"26\" text-anchor=\"middle\" font-size=\"9\" fill=\"#000\">(Longitud: {$longitudTexto})</text>";
        }

        $y = 32;
        $maxItems = 4;
        $itemCount = 0;
        $letraIndex = 0;
        $letras = range('A', 'Z');

        // Crear mapa de letras desde armaduraConLetras
        $mapaLetras = [];
        foreach ($armaduraConLetras as $arm) {
            $key = $arm['diametro'] . '_' . ($arm['tipo'] ?? 'long');
            $mapaLetras[$key] = $arm['letra'];
        }

        // Renderizar barras
        foreach ($barras as $barra) {
            if ($itemCount >= $maxItems) break;

            $key = ($barra['diametro'] ?? '?') . '_longitudinal';
            $letra = $mapaLetras[$key] ?? $letras[$letraIndex] ?? '?';

            $barra['tipo'] = 'barra';
            $svg .= self::renderizarForma($barra, $cotasEntidad, $letra, [
                'x' => 0,
                'y' => $y,
                'maxWidth' => 350,
            ]);

            $y += 42;
            $itemCount++;
            $letraIndex++;
        }

        // Renderizar estribos
        foreach ($estribos as $estribo) {
            if ($itemCount >= $maxItems) break;

            $key = ($estribo['diametro'] ?? '?') . '_transversal';
            $letra = $mapaLetras[$key] ?? $letras[$letraIndex] ?? 'E';

            $estribo['tipo'] = 'estribo';
            $svg .= self::renderizarForma($estribo, null, $letra, [
                'x' => 0,
                'y' => $y,
                'maxWidth' => 350,
            ]);

            $y += 42;
            $itemCount++;
            $letraIndex++;
        }

        // Si no hay formas detalladas, mostrar mensaje
        if ($itemCount === 0) {
            $svg .= "<text x=\"175\" y=\"100\" text-anchor=\"middle\" font-size=\"10\" fill=\"#666\">(Sin datos de forma detallada)</text>";

            // Mostrar cotas si existen
            if ($cotasEntidad) {
                $svg .= "<text x=\"175\" y=\"120\" text-anchor=\"middle\" font-size=\"9\" fill=\"#333\">Cotas: {$cotasEntidad}</text>";
            }
        }

        $svg .= "</g>";

        return $svg;
    }

    /**
     * Calcula los puntos del path a partir de la secuencia de doblado.
     */
    private static function calcularPuntos(array $secuencia): array
    {
        $x = 0;
        $y = 0;
        $direccion = 0; // grados, 0 = derecha

        $puntos = [['x' => $x, 'y' => $y]];

        foreach ($secuencia as $segmento) {
            $tipo = $segmento['tipo'] ?? '';

            if ($tipo === 'longitud') {
                $longitud = (float)($segmento['valor'] ?? 0);
                if ($longitud <= 0) continue;

                // En SVG, Y crece hacia abajo
                $rad = deg2rad($direccion);
                $x += cos($rad) * $longitud;
                $y += sin($rad) * $longitud;

                $puntos[] = ['x' => $x, 'y' => $y];
            }
            elseif ($tipo === 'doblez') {
                // Ángulo positivo = giro horario (en sistema de coordenadas SVG)
                $angulo = (float)($segmento['angulo'] ?? 0);
                $direccion += $angulo;
            }
            // 'radio' se ignora por ahora
        }

        return $puntos;
    }

    /**
     * Calcula los bounds (límites) de un conjunto de puntos.
     */
    private static function calcularBounds(array $puntos): array
    {
        if (empty($puntos)) {
            return ['minX' => 0, 'maxX' => 0, 'minY' => 0, 'maxY' => 0, 'width' => 0, 'height' => 0];
        }

        $minX = $maxX = $puntos[0]['x'];
        $minY = $maxY = $puntos[0]['y'];

        foreach ($puntos as $punto) {
            $minX = min($minX, $punto['x']);
            $maxX = max($maxX, $punto['x']);
            $minY = min($minY, $punto['y']);
            $maxY = max($maxY, $punto['y']);
        }

        return [
            'minX' => $minX,
            'maxX' => $maxX,
            'minY' => $minY,
            'maxY' => $maxY,
            'width' => $maxX - $minX,
            'height' => $maxY - $minY,
        ];
    }

    /**
     * Formatea las dimensiones de un elemento para mostrar en texto.
     */
    private static function formatearDimensiones(array $elemento, ?string $cotas): string
    {
        // Si hay cotas, usarlas
        if ($cotas && strlen(trim($cotas)) > 0) {
            // Limpiar y acortar si es muy largo
            $cotas = trim($cotas);
            if (strlen($cotas) > 30) {
                $cotas = substr($cotas, 0, 27) . '...';
            }
            return $cotas;
        }

        // Si hay secuencia de doblado, calcular dimensiones
        $secuencia = $elemento['secuencia_doblado'] ?? [];
        if (!empty($secuencia)) {
            $longitudes = [];
            foreach ($secuencia as $seg) {
                if (($seg['tipo'] ?? '') === 'longitud' && ($seg['valor'] ?? 0) > 0) {
                    $longitudes[] = round($seg['valor']);
                }
            }
            if (!empty($longitudes)) {
                return implode(' + ', $longitudes) . 'mm';
            }
        }

        // Si hay longitud total
        $longitud = $elemento['longitud'] ?? 0;
        if ($longitud > 0) {
            return round($longitud) . 'mm';
        }

        return '';
    }
}
