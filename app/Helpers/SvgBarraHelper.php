<?php

namespace App\Helpers;

/**
 * Helper para generar planos de ensamblado en formato SVG.
 *
 * Genera representaciones visuales profesionales de entidades de armadura
 * para que los operarios puedan ensamblar correctamente.
 */
class SvgBarraHelper
{
    // Colores para cada tipo de elemento
    const COLORES = [
        'A' => '#2563eb', // Azul
        'B' => '#059669', // Verde
        'C' => '#dc2626', // Rojo
        'D' => '#7c3aed', // Púrpura
        'E' => '#f59e0b', // Naranja (estribos)
        'F' => '#06b6d4', // Cyan
    ];

    /**
     * Genera el plano completo de ensamblado.
     */
    public static function generarPlanoEnsamblado(array $datos): string
    {
        $longitudCm = ($datos['longitud'] ?? 0) * 100;
        $estriboAncho = $datos['estriboAncho'] ?? 25;
        $estriboAlto = $datos['estriboAlto'] ?? 30;
        $totalSuperiores = $datos['totalSuperiores'] ?? 2;
        $totalInferiores = $datos['totalInferiores'] ?? 2;
        $cantidadEstribos = $datos['cantidadEstribos'] ?? 10;
        $separacionEstribos = $datos['separacionEstribos'] ?? 15;
        $armaduraConLetras = $datos['armaduraConLetras'] ?? [];
        $elementosPorLetra = $datos['elementosPorLetra'] ?? collect();
        $letraSup = $datos['letraSup'] ?? 'A';
        $letraInf = $datos['letraInf'] ?? 'B';
        $letraEstribo = $datos['letraEstribo'] ?? 'C';
        $composicion = $datos['composicion'] ?? [];

        $svg = '';

        // === VISTA 3D ISOMÉTRICA (izquierda) ===
        $svg .= self::renderizarVista3D([
            'x' => 5,
            'y' => 5,
            'width' => 280,
            'height' => 180,
            'longitudCm' => $longitudCm,
            'estriboAncho' => $estriboAncho,
            'estriboAlto' => $estriboAlto,
            'cantidadEstribos' => $cantidadEstribos,
            'separacionEstribos' => $separacionEstribos,
            'armaduraConLetras' => $armaduraConLetras,
            'letraSup' => $letraSup,
            'letraInf' => $letraInf,
            'letraEstribo' => $letraEstribo,
        ]);

        // === SECCIÓN TRANSVERSAL CON POSICIONES (derecha arriba) ===
        $svg .= self::renderizarSeccionDetallada([
            'x' => 295,
            'y' => 5,
            'width' => 260,
            'height' => 180,
            'estriboAncho' => $estriboAncho,
            'estriboAlto' => $estriboAlto,
            'armaduraConLetras' => $armaduraConLetras,
            'composicion' => $composicion,
            'letraEstribo' => $letraEstribo,
        ]);

        // === LEYENDA DE ELEMENTOS (abajo) ===
        $svg .= self::renderizarLeyenda([
            'x' => 5,
            'y' => 190,
            'width' => 550,
            'height' => 60,
            'armaduraConLetras' => $armaduraConLetras,
            'elementosPorLetra' => $elementosPorLetra,
            'composicion' => $composicion,
            'longitudCm' => $longitudCm,
        ]);

        return $svg;
    }

    /**
     * Renderiza vista 3D isométrica simplificada.
     * Blanco y negro para impresión.
     */
    public static function renderizarVista3D(array $opciones): string
    {
        $x = $opciones['x'] ?? 5;
        $y = $opciones['y'] ?? 5;
        $width = $opciones['width'] ?? 280;
        $height = $opciones['height'] ?? 180;
        $longitudCm = $opciones['longitudCm'] ?? 350;
        $estriboAncho = $opciones['estriboAncho'] ?? 25;
        $estriboAlto = $opciones['estriboAlto'] ?? 30;
        $cantidadEstribos = $opciones['cantidadEstribos'] ?? 10;
        $separacionEstribos = $opciones['separacionEstribos'] ?? 15;
        $armaduraConLetras = $opciones['armaduraConLetras'] ?? [];
        $letraEstribo = $opciones['letraEstribo'] ?? 'E';

        $svg = "<g transform=\"translate({$x}, {$y})\">";

        // Título
        $svg .= "<text x=\"" . ($width/2) . "\" y=\"12\" text-anchor=\"middle\" font-size=\"10\" font-weight=\"bold\" fill=\"#000\">VISTA 3D</text>";

        // Parámetros isométricos
        $isoAngle = 30;
        $cos30 = cos(deg2rad($isoAngle));
        $sin30 = sin(deg2rad($isoAngle));

        // Escala para que quepa
        $maxLongitud = max($longitudCm, 100);
        $escalaL = ($width - 80) / $maxLongitud * 50;
        $escalaS = min(($height - 60) / max($estriboAlto, 20), 2);

        // Centro de la vista
        $cx = $width / 2;
        $cy = $height / 2 + 15;

        // Dimensiones escaladas
        $longPx = min($longitudCm * $escalaL / 50, $width - 100);
        $anchoPx = $estriboAncho * $escalaS;
        $altoPx = $estriboAlto * $escalaS;

        // Función para proyectar punto 3D a 2D isométrico
        $proyecto = function($px, $py, $pz) use ($cx, $cy, $cos30, $sin30) {
            $isoX = $cx + ($px - $pz) * $cos30;
            $isoY = $cy - $py + ($px + $pz) * $sin30 * 0.5;
            return [$isoX, $isoY];
        };

        // Dibujar estribos (rectángulos en perspectiva) - gris para profundidad
        $numEstribosVis = min($cantidadEstribos, 8);
        $espacioEstribo = $longPx / max($numEstribosVis + 1, 2);

        for ($i = 1; $i <= $numEstribosVis; $i++) {
            $posZ = -$longPx/2 + $i * $espacioEstribo;
            // Opacidad variable para dar profundidad
            $opacity = 0.3 + (0.5 * $i / $numEstribosVis);

            $p1 = $proyecto(-$anchoPx/2, $altoPx/2, $posZ);
            $p2 = $proyecto($anchoPx/2, $altoPx/2, $posZ);
            $p3 = $proyecto($anchoPx/2, -$altoPx/2, $posZ);
            $p4 = $proyecto(-$anchoPx/2, -$altoPx/2, $posZ);

            $svg .= "<path d=\"M {$p1[0]} {$p1[1]} L {$p2[0]} {$p2[1]} L {$p3[0]} {$p3[1]} L {$p4[0]} {$p4[1]} Z\" fill=\"none\" stroke=\"#000\" stroke-width=\"1.5\" opacity=\"{$opacity}\"/>";
        }

        // Dibujar barras longitudinales
        $barrasPos = self::calcularPosicionesBarras($armaduraConLetras, $anchoPx, $altoPx);

        foreach ($barrasPos as $barra) {
            $letra = $barra['letra'];
            $bx = $barra['x'];
            $by = $barra['y'];

            $pStart = $proyecto($bx, $by, -$longPx/2);
            $pEnd = $proyecto($bx, $by, $longPx/2);

            // Barra en negro
            $svg .= "<line x1=\"{$pStart[0]}\" y1=\"{$pStart[1]}\" x2=\"{$pEnd[0]}\" y2=\"{$pEnd[1]}\" stroke=\"#000\" stroke-width=\"3\" stroke-linecap=\"round\"/>";

            // Círculo con letra en el extremo frontal
            $svg .= "<circle cx=\"{$pEnd[0]}\" cy=\"{$pEnd[1]}\" r=\"6\" fill=\"#fff\" stroke=\"#000\" stroke-width=\"1.5\"/>";
            $svg .= "<text x=\"{$pEnd[0]}\" y=\"" . ($pEnd[1] + 3) . "\" text-anchor=\"middle\" font-size=\"8\" fill=\"#000\" font-weight=\"bold\">{$letra}</text>";
        }

        // Cotas de longitud
        $cotaY = $cy + $altoPx/2 + 25;
        $cotaX1 = $cx - $longPx/2 * $cos30;
        $cotaX2 = $cx + $longPx/2 * $cos30;

        $svg .= "<line x1=\"{$cotaX1}\" y1=\"{$cotaY}\" x2=\"{$cotaX2}\" y2=\"{$cotaY}\" stroke=\"#000\" stroke-width=\"0.5\"/>";
        $svg .= "<line x1=\"{$cotaX1}\" y1=\"" . ($cotaY - 3) . "\" x2=\"{$cotaX1}\" y2=\"" . ($cotaY + 3) . "\" stroke=\"#000\" stroke-width=\"0.5\"/>";
        $svg .= "<line x1=\"{$cotaX2}\" y1=\"" . ($cotaY - 3) . "\" x2=\"{$cotaX2}\" y2=\"" . ($cotaY + 3) . "\" stroke=\"#000\" stroke-width=\"0.5\"/>";

        $longitudTexto = number_format($longitudCm / 100, 2) . 'm';
        $svg .= "<text x=\"{$cx}\" y=\"" . ($cotaY + 12) . "\" text-anchor=\"middle\" font-size=\"10\" fill=\"#000\" font-weight=\"bold\">L = {$longitudTexto}</text>";

        // Indicador de separación de estribos
        if ($separacionEstribos > 0) {
            $svg .= "<text x=\"" . ($width - 10) . "\" y=\"30\" text-anchor=\"end\" font-size=\"9\" fill=\"#000\">Estribos c/{$separacionEstribos}cm</text>";
        }

        $svg .= "</g>";
        return $svg;
    }

    /**
     * Renderiza sección transversal con posiciones exactas de barras.
     * Diseñado para impresión en blanco y negro.
     */
    public static function renderizarSeccionDetallada(array $opciones): string
    {
        $x = $opciones['x'] ?? 295;
        $y = $opciones['y'] ?? 5;
        $width = $opciones['width'] ?? 260;
        $height = $opciones['height'] ?? 180;
        $estriboAncho = $opciones['estriboAncho'] ?? 25;
        $estriboAlto = $opciones['estriboAlto'] ?? 30;
        $armaduraConLetras = $opciones['armaduraConLetras'] ?? [];
        $composicion = $opciones['composicion'] ?? [];
        $letraEstribo = $opciones['letraEstribo'] ?? 'E';

        $svg = "<g transform=\"translate({$x}, {$y})\">";

        // Título
        $svg .= "<text x=\"" . ($width/2) . "\" y=\"12\" text-anchor=\"middle\" font-size=\"10\" font-weight=\"bold\" fill=\"#000\">SECCIÓN TRANSVERSAL</text>";

        // Área de dibujo
        $drawX = 40;
        $drawY = 25;
        $drawW = $width - 80;
        $drawH = $height - 60;

        // Escala para el estribo
        $escalaX = $drawW / max($estriboAncho, 20);
        $escalaY = $drawH / max($estriboAlto, 20);
        $escala = min($escalaX, $escalaY) * 0.8;

        $estriboW = $estriboAncho * $escala;
        $estriboH = $estriboAlto * $escala;

        // Centrar estribo
        $estriboX = $drawX + ($drawW - $estriboW) / 2;
        $estriboY = $drawY + ($drawH - $estriboH) / 2;

        // Estribo en blanco y negro
        $svg .= "<rect x=\"{$estriboX}\" y=\"{$estriboY}\" width=\"{$estriboW}\" height=\"{$estriboH}\" fill=\"#fff\" stroke=\"#000\" stroke-width=\"2\" rx=\"2\"/>";

        // Badge del estribo (esquina)
        $svg .= "<rect x=\"" . ($estriboX + $estriboW - 18) . "\" y=\"{$estriboY}\" width=\"18\" height=\"14\" fill=\"#000\" rx=\"0 2 0 2\"/>";
        $svg .= "<text x=\"" . ($estriboX + $estriboW - 9) . "\" y=\"" . ($estriboY + 10) . "\" text-anchor=\"middle\" font-size=\"9\" fill=\"#fff\" font-weight=\"bold\">{$letraEstribo}</text>";

        // Calcular posiciones de barras dentro del estribo
        $recubrimiento = 3 * $escala; // 3cm recubrimiento
        $innerX = $estriboX + $recubrimiento;
        $innerY = $estriboY + $recubrimiento;
        $innerW = $estriboW - 2 * $recubrimiento;
        $innerH = $estriboH - 2 * $recubrimiento;

        // Dibujar barras según posición
        $barrasComp = $composicion['barras'] ?? [];
        $radioBase = min($innerW, $innerH) / 12;

        foreach ($armaduraConLetras as $arm) {
            if ($arm['tipo'] !== 'longitudinal') continue;

            $letra = $arm['letra'];
            $cantidad = $arm['cantidad'] ?? 0;
            $posicion = $arm['posicion'] ?? 'superior';
            $diametro = $arm['diametro'] ?? 12;

            // Radio proporcional al diámetro
            $radio = max(8, $radioBase * ($diametro / 12));

            // Buscar info adicional en composición
            $posicionComp = null;
            foreach ($barrasComp as $bc) {
                if (($bc['diametro'] ?? 0) == $diametro) {
                    $posicionComp = $bc['posicion'] ?? null;
                    break;
                }
            }

            // Determinar coordenadas según posición
            $coords = self::calcularCoordenadasSeccion($posicion, $posicionComp, $cantidad, $innerX, $innerY, $innerW, $innerH, $radio);

            // Dibujar cada barra con su letra (blanco y negro)
            foreach ($coords as $coord) {
                // Círculo negro con relleno blanco
                $svg .= "<circle cx=\"{$coord['x']}\" cy=\"{$coord['y']}\" r=\"{$radio}\" fill=\"#fff\" stroke=\"#000\" stroke-width=\"1.5\"/>";

                // Letra en cada barra
                $fontSize = max(7, min(10, $radio * 0.9));
                $svg .= "<text x=\"{$coord['x']}\" y=\"" . ($coord['y'] + $fontSize/3) . "\" text-anchor=\"middle\" font-size=\"{$fontSize}\" fill=\"#000\" font-weight=\"bold\">{$letra}</text>";
            }
        }

        // Cotas del estribo
        // Cota horizontal (ancho)
        $cotaHY = $estriboY + $estriboH + 10;
        $svg .= "<line x1=\"{$estriboX}\" y1=\"{$cotaHY}\" x2=\"" . ($estriboX + $estriboW) . "\" y2=\"{$cotaHY}\" stroke=\"#000\" stroke-width=\"0.5\"/>";
        $svg .= "<line x1=\"{$estriboX}\" y1=\"" . ($cotaHY - 3) . "\" x2=\"{$estriboX}\" y2=\"" . ($cotaHY + 3) . "\" stroke=\"#000\" stroke-width=\"0.5\"/>";
        $svg .= "<line x1=\"" . ($estriboX + $estriboW) . "\" y1=\"" . ($cotaHY - 3) . "\" x2=\"" . ($estriboX + $estriboW) . "\" y2=\"" . ($cotaHY + 3) . "\" stroke=\"#000\" stroke-width=\"0.5\"/>";
        $svg .= "<text x=\"" . ($estriboX + $estriboW/2) . "\" y=\"" . ($cotaHY + 12) . "\" text-anchor=\"middle\" font-size=\"9\" fill=\"#000\">{$estriboAncho} cm</text>";

        // Cota vertical (alto)
        $cotaVX = $estriboX + $estriboW + 10;
        $svg .= "<line x1=\"{$cotaVX}\" y1=\"{$estriboY}\" x2=\"{$cotaVX}\" y2=\"" . ($estriboY + $estriboH) . "\" stroke=\"#000\" stroke-width=\"0.5\"/>";
        $svg .= "<line x1=\"" . ($cotaVX - 3) . "\" y1=\"{$estriboY}\" x2=\"" . ($cotaVX + 3) . "\" y2=\"{$estriboY}\" stroke=\"#000\" stroke-width=\"0.5\"/>";
        $svg .= "<line x1=\"" . ($cotaVX - 3) . "\" y1=\"" . ($estriboY + $estriboH) . "\" x2=\"" . ($cotaVX + 3) . "\" y2=\"" . ($estriboY + $estriboH) . "\" stroke=\"#000\" stroke-width=\"0.5\"/>";

        // Texto vertical
        $textY = $estriboY + $estriboH/2;
        $svg .= "<text x=\"" . ($cotaVX + 12) . "\" y=\"{$textY}\" text-anchor=\"middle\" font-size=\"9\" fill=\"#000\" transform=\"rotate(90, " . ($cotaVX + 12) . ", {$textY})\">{$estriboAlto} cm</text>";

        // Indicador de recubrimiento
        $svg .= "<text x=\"" . ($estriboX + 5) . "\" y=\"" . ($estriboY - 3) . "\" font-size=\"7\" fill=\"#666\">rec. 3cm</text>";

        $svg .= "</g>";
        return $svg;
    }

    /**
     * Renderiza leyenda compacta de elementos con códigos, formas y cotas.
     * Los elementos son clickeables para mostrar su ubicación en el mapa.
     */
    public static function renderizarLeyenda(array $opciones): string
    {
        $x = $opciones['x'] ?? 5;
        $y = $opciones['y'] ?? 190;
        $width = $opciones['width'] ?? 550;
        $armaduraConLetras = $opciones['armaduraConLetras'] ?? [];
        $elementosPorLetra = $opciones['elementosPorLetra'] ?? collect();
        $composicion = $opciones['composicion'] ?? [];

        $numElementos = count($armaduraConLetras);
        $height = 60; // Altura aumentada para incluir cotas

        $svg = "<g transform=\"translate({$x}, {$y})\">";

        // Fondo
        $svg .= "<rect x=\"0\" y=\"0\" width=\"{$width}\" height=\"{$height}\" fill=\"#fff\" stroke=\"#000\" stroke-width=\"1\" rx=\"2\"/>";

        // Calcular ancho por elemento (distribuir equitativamente)
        $itemWidth = $width / max($numElementos, 1);
        $formaWidth = min(60, $itemWidth - 20);
        $formaHeight = 18;

        // Preparar mapa de dimensiones desde la composición
        $barrasComp = $composicion['barras'] ?? [];
        $estribosComp = $composicion['estribos'] ?? [];

        foreach ($armaduraConLetras as $index => $arm) {
            $letra = $arm['letra'] ?? '?';
            $tipo = $arm['tipo'] ?? 'longitudinal';
            $diametro = $arm['diametro'] ?? '?';
            $cantidad = $arm['cantidad'] ?? 0;
            $esEstribo = $tipo === 'transversal';

            $itemX = $index * $itemWidth + 5;

            // Buscar elemento real y su ubicación
            $codigoElem = '';
            $longitud = 0;
            $dimensiones = '';
            $paqueteCodigo = '';
            $tieneUbicacion = false;

            // Buscar datos del elemento asociado (dimensiones, código, ubicación)
            if ($elementosPorLetra && isset($elementosPorLetra[$letra])) {
                $elem = $elementosPorLetra[$letra]->first();
                if ($elem) {
                    $codigoElem = $elem->etiqueta_sub_id ?? $elem->codigo ?? '';
                    $dimensiones = $elem->dimensiones ?? '';
                    $longitud = $elem->longitud ?? 0;
                    // Obtener código del paquete si tiene ubicación
                    if (isset($elem->ubicacion_coords) && $elem->ubicacion_coords) {
                        $tieneUbicacion = true;
                        $paquete = $elem->etiquetaRelacion->paquete ?? null;
                        $paqueteCodigo = $paquete->codigo ?? '';
                    }
                }
            }

            // Grupo clickeable con data attributes
            $dataAttrs = "data-letra=\"{$letra}\" data-codigo=\"" . htmlspecialchars($codigoElem) . "\"";
            if ($tieneUbicacion) {
                $dataAttrs .= " data-paquete=\"" . htmlspecialchars($paqueteCodigo) . "\" data-tiene-ubicacion=\"1\"";
            }
            $cursorStyle = $tieneUbicacion ? 'cursor: pointer;' : '';
            $svg .= "<g class=\"leyenda-elemento\" {$dataAttrs} style=\"{$cursorStyle}\">";

            // Área clickeable invisible
            $svg .= "<rect x=\"{$itemX}\" y=\"0\" width=\"" . ($itemWidth - 5) . "\" height=\"{$height}\" fill=\"transparent\" class=\"click-area\"/>";

            // Línea 1: Letra + Código
            $svg .= "<circle cx=\"" . ($itemX + 6) . "\" cy=\"8\" r=\"6\" fill=\"#000\"/>";
            $svg .= "<text x=\"" . ($itemX + 6) . "\" y=\"11\" text-anchor=\"middle\" font-size=\"8\" fill=\"#fff\" font-weight=\"bold\">{$letra}</text>";

            $codigoDisplay = $codigoElem;
            if (strlen($codigoDisplay) > 10) {
                $codigoDisplay = substr($codigoDisplay, -8);
            }
            $svg .= "<text x=\"" . ($itemX + 15) . "\" y=\"11\" font-size=\"7\" fill=\"#000\" font-weight=\"bold\">{$codigoDisplay}</text>";

            // Icono de ubicación si tiene
            if ($tieneUbicacion) {
                $iconX = $itemX + $itemWidth - 18;
                $svg .= "<g transform=\"translate({$iconX}, 2)\">";
                $svg .= "<circle cx=\"6\" cy=\"6\" r=\"5\" fill=\"#3b82f6\" opacity=\"0.9\"/>";
                $svg .= "<path d=\"M6 3 C4 3 3 4.5 3 6 C3 8 6 10 6 10 C6 10 9 8 9 6 C9 4.5 8 3 6 3 Z M6 7 C5.4 7 5 6.6 5 6 C5 5.4 5.4 5 6 5 C6.6 5 7 5.4 7 6 C7 6.6 6.6 7 6 7 Z\" fill=\"#fff\"/>";
                $svg .= "</g>";
            }

            // Línea 2: Forma del elemento
            $formaX = $itemX;
            $formaY = 14;

            if (!empty($dimensiones)) {
                $svg .= self::dibujarFormaElemento($dimensiones, $formaX, $formaY, $formaWidth, $formaHeight, $esEstribo);
            } else {
                if ($esEstribo) {
                    $svg .= "<rect x=\"" . ($formaX + 10) . "\" y=\"" . ($formaY + 2) . "\" width=\"20\" height=\"12\" fill=\"none\" stroke=\"#000\" stroke-width=\"1.5\" rx=\"1\"/>";
                } else {
                    $svg .= "<line x1=\"{$formaX}\" y1=\"" . ($formaY + 8) . "\" x2=\"" . ($formaX + $formaWidth - 5) . "\" y2=\"" . ($formaY + 8) . "\" stroke=\"#000\" stroke-width=\"2\" stroke-linecap=\"round\"/>";
                }
            }

            // Línea 3: Cotas/dimensiones del elemento
            if (!empty($dimensiones)) {
                $cotasTexto = self::formatearCotasLegible($dimensiones);
                $svg .= "<text x=\"{$itemX}\" y=\"36\" font-size=\"6\" fill=\"#555\" font-style=\"italic\">{$cotasTexto}</text>";
            }

            // Línea 4: Cantidad y medidas
            $desc = "{$cantidad}×Ø{$diametro}";
            if ($longitud > 0) {
                // elemento.longitud está en mm, convertir a metros
                $longM = number_format($longitud / 1000, 1) . 'm';
                $desc .= " {$longM}";
            }
            if ($esEstribo && isset($arm['separacion'])) {
                $desc = "{$cantidad}×Ø{$diametro} c/" . $arm['separacion'];
            }
            $svg .= "<text x=\"{$itemX}\" y=\"56\" font-size=\"7\" fill=\"#000\">{$desc}</text>";

            $svg .= "</g>"; // Cerrar grupo clickeable

            // Separador vertical (excepto último)
            if ($index < $numElementos - 1) {
                $sepX = ($index + 1) * $itemWidth;
                $svg .= "<line x1=\"{$sepX}\" y1=\"3\" x2=\"{$sepX}\" y2=\"" . ($height - 3) . "\" stroke=\"#ccc\" stroke-width=\"0.5\"/>";
            }
        }

        $svg .= "</g>";
        return $svg;
    }

    /**
     * Calcula posiciones de barras para la vista 3D.
     */
    private static function calcularPosicionesBarras(array $armaduraConLetras, float $ancho, float $alto): array
    {
        $barras = [];
        $recub = min($ancho, $alto) * 0.15; // 15% recubrimiento

        foreach ($armaduraConLetras as $arm) {
            if ($arm['tipo'] !== 'longitudinal') continue;

            $letra = $arm['letra'];
            $cantidad = $arm['cantidad'] ?? 0;
            $posicion = $arm['posicion'] ?? 'superior';

            // Distribuir barras según posición
            for ($i = 0; $i < min($cantidad, 4); $i++) {
                $bx = 0;
                $by = 0;

                switch ($posicion) {
                    case 'superior':
                        $by = $alto/2 - $recub;
                        $bx = -$ancho/2 + $recub + ($i * ($ancho - 2*$recub) / max($cantidad - 1, 1));
                        break;
                    case 'inferior':
                        $by = -$alto/2 + $recub;
                        $bx = -$ancho/2 + $recub + ($i * ($ancho - 2*$recub) / max($cantidad - 1, 1));
                        break;
                    case 'lateral':
                    case 'piel':
                        $lado = $i % 2 == 0 ? -1 : 1;
                        $bx = $lado * ($ancho/2 - $recub);
                        $by = -$alto/4 + ($i/2) * ($alto/2);
                        break;
                    case 'esquina':
                    default:
                        // Esquinas
                        $esquinas = [
                            [-$ancho/2 + $recub, $alto/2 - $recub],
                            [$ancho/2 - $recub, $alto/2 - $recub],
                            [-$ancho/2 + $recub, -$alto/2 + $recub],
                            [$ancho/2 - $recub, -$alto/2 + $recub],
                        ];
                        if ($i < count($esquinas)) {
                            $bx = $esquinas[$i][0];
                            $by = $esquinas[$i][1];
                        }
                        break;
                }

                $barras[] = ['letra' => $letra, 'x' => $bx, 'y' => $by];
            }
        }

        return $barras;
    }

    /**
     * Calcula coordenadas de barras en la sección transversal.
     */
    private static function calcularCoordenadasSeccion(
        string $posicion,
        ?string $posicionComp,
        int $cantidad,
        float $innerX,
        float $innerY,
        float $innerW,
        float $innerH,
        float $radio
    ): array {
        $coords = [];
        $margen = $radio * 1.5;

        // Usar posición de composición si está disponible
        $pos = $posicionComp ?? $posicion;

        switch ($pos) {
            case 'esquina':
                // 4 esquinas
                $esquinas = [
                    ['x' => $innerX + $margen, 'y' => $innerY + $margen],
                    ['x' => $innerX + $innerW - $margen, 'y' => $innerY + $margen],
                    ['x' => $innerX + $margen, 'y' => $innerY + $innerH - $margen],
                    ['x' => $innerX + $innerW - $margen, 'y' => $innerY + $innerH - $margen],
                ];
                for ($i = 0; $i < min($cantidad, 4); $i++) {
                    $coords[] = $esquinas[$i];
                }
                break;

            case 'superior':
                // Distribuir en la parte superior
                $espacio = ($innerW - 2 * $margen) / max($cantidad - 1, 1);
                for ($i = 0; $i < $cantidad; $i++) {
                    $coords[] = [
                        'x' => $innerX + $margen + $i * $espacio,
                        'y' => $innerY + $margen,
                    ];
                }
                break;

            case 'inferior':
                // Distribuir en la parte inferior
                $espacio = ($innerW - 2 * $margen) / max($cantidad - 1, 1);
                for ($i = 0; $i < $cantidad; $i++) {
                    $coords[] = [
                        'x' => $innerX + $margen + $i * $espacio,
                        'y' => $innerY + $innerH - $margen,
                    ];
                }
                break;

            case 'lateral':
            case 'piel':
                // Distribuir en los laterales
                $porLado = ceil($cantidad / 2);
                $espacioY = ($innerH - 2 * $margen) / max($porLado + 1, 2);
                for ($i = 0; $i < $cantidad; $i++) {
                    $lado = $i % 2 == 0 ? 0 : 1; // 0=izquierda, 1=derecha
                    $posY = floor($i / 2) + 1;
                    $coords[] = [
                        'x' => $lado == 0 ? $innerX + $margen : $innerX + $innerW - $margen,
                        'y' => $innerY + $margen + $posY * $espacioY,
                    ];
                }
                break;

            default:
                // Distribución automática (superior/inferior mitad y mitad)
                $mitad = ceil($cantidad / 2);
                $espacioSup = ($innerW - 2 * $margen) / max($mitad - 1, 1);
                $espacioInf = ($innerW - 2 * $margen) / max($cantidad - $mitad - 1, 1);

                for ($i = 0; $i < $cantidad; $i++) {
                    if ($i < $mitad) {
                        $coords[] = [
                            'x' => $innerX + $margen + $i * $espacioSup,
                            'y' => $innerY + $margen,
                        ];
                    } else {
                        $idx = $i - $mitad;
                        $coords[] = [
                            'x' => $innerX + $margen + $idx * $espacioInf,
                            'y' => $innerY + $innerH - $margen,
                        ];
                    }
                }
                break;
        }

        return $coords;
    }

    /**
     * Dibuja la forma de un elemento basándose en sus dimensiones.
     * Usa blanco y negro para impresión.
     */
    public static function dibujarFormaElemento(string $dimensiones, float $x, float $y, float $width, float $height, bool $esEstribo = false): string
    {
        $color = '#000'; // Blanco y negro
        $dims = self::parsearDimensionesCompleto($dimensiones);

        if (empty($dims)) {
            $centerY = $y + $height / 2;
            return "<line x1=\"{$x}\" y1=\"{$centerY}\" x2=\"" . ($x + $width - 5) . "\" y2=\"{$centerY}\" stroke=\"{$color}\" stroke-width=\"2\" stroke-linecap=\"round\"/>";
        }

        $puntos = self::calcularPuntosCompleto($dims);

        if (count($puntos) < 2) {
            $centerY = $y + $height / 2;
            return "<line x1=\"{$x}\" y1=\"{$centerY}\" x2=\"" . ($x + $width - 5) . "\" y2=\"{$centerY}\" stroke=\"{$color}\" stroke-width=\"2\" stroke-linecap=\"round\"/>";
        }

        $bounds = self::calcularBounds($puntos);
        $needsRotation = $bounds['height'] > $bounds['width'];

        if ($needsRotation) {
            $cx = ($bounds['minX'] + $bounds['maxX']) / 2;
            $cy = ($bounds['minY'] + $bounds['maxY']) / 2;
            $puntos = array_map(fn($p) => self::rotarPunto($p, $cx, $cy, 90), $puntos);
            $bounds = self::calcularBounds($puntos);
        }

        $margen = 3;
        $anchoDisp = $width - (2 * $margen);
        $altoDisp = $height - (2 * $margen);

        $escalaX = $anchoDisp / max(1, $bounds['width']);
        $escalaY = $altoDisp / max(1, $bounds['height']);
        $escala = min($escalaX, $escalaY);

        $centroX = $x + $width / 2;
        $centroY = $y + $height / 2;
        $midX = ($bounds['minX'] + $bounds['maxX']) / 2;
        $midY = ($bounds['minY'] + $bounds['maxY']) / 2;

        $pathData = '';
        foreach ($puntos as $i => $punto) {
            $px = round($centroX + ($punto['x'] - $midX) * $escala, 2);
            $py = round($centroY + ($punto['y'] - $midY) * $escala, 2);
            $pathData .= ($i === 0 ? "M {$px} {$py}" : " L {$px} {$py}");
        }

        return "<path d=\"{$pathData}\" fill=\"none\" stroke=\"{$color}\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>";
    }

    /**
     * Parsea dimensiones FerraWin completo.
     */
    public static function parsearDimensionesCompleto(string $dimensiones): array
    {
        if (empty(trim($dimensiones))) return [];

        $dims = [];
        $tokens = preg_split('/\s+/', trim($dimensiones));

        for ($i = 0; $i < count($tokens); $i++) {
            $token = trim($tokens[$i]);
            if (empty($token)) continue;

            if (preg_match('/^([\d.]+)r$/i', $token, $matches)) {
                $radius = (float)$matches[1];
                $arcAngle = 360;
                if ($i + 1 < count($tokens) && preg_match('/^([\d.]+)d$/i', $tokens[$i + 1], $m)) {
                    $arcAngle = (float)$m[1];
                    $i++;
                }
                $dims[] = ['type' => 'arc', 'radius' => $radius, 'arcAngle' => $arcAngle];
            } elseif (preg_match('/^([\d.]+)d$/i', $token, $matches)) {
                $dims[] = ['type' => 'turn', 'angle' => (float)$matches[1]];
            } elseif (is_numeric($token)) {
                $length = (float)$token;
                if ($length > 0) $dims[] = ['type' => 'line', 'length' => $length];
            }
        }

        return $dims;
    }

    private static function calcularPuntosCompleto(array $dims): array
    {
        $x = 0; $y = 0; $ang = 0;
        $puntos = [['x' => $x, 'y' => $y]];

        foreach ($dims as $d) {
            $type = $d['type'] ?? '';

            if ($type === 'line') {
                $length = $d['length'] ?? 0;
                if ($length <= 0) continue;
                $rad = deg2rad($ang);
                $x += cos($rad) * $length;
                $y += sin($rad) * $length;
                $puntos[] = ['x' => $x, 'y' => $y];
            } elseif ($type === 'turn') {
                $ang += $d['angle'] ?? 0;
            } elseif ($type === 'arc') {
                $radius = $d['radius'] ?? 0;
                $arcAngle = $d['arcAngle'] ?? 360;
                $radStart = deg2rad($ang + 90);
                $cx = $x + $radius * cos($radStart);
                $cy = $y + $radius * sin($radStart);
                $startAngle = atan2($y - $cy, $x - $cx);
                $endAngle = $startAngle + deg2rad($arcAngle);
                $x = $cx + $radius * cos($endAngle);
                $y = $cy + $radius * sin($endAngle);
                $ang += $arcAngle;
                $puntos[] = ['x' => $x, 'y' => $y];
            }
        }

        return $puntos;
    }

    private static function rotarPunto(array $punto, float $cx, float $cy, float $angGrados): array
    {
        $rad = deg2rad($angGrados);
        $dx = $punto['x'] - $cx;
        $dy = $punto['y'] - $cy;
        return [
            'x' => $cx + $dx * cos($rad) - $dy * sin($rad),
            'y' => $cy + $dx * sin($rad) + $dy * cos($rad),
        ];
    }

    private static function calcularBounds(array $puntos): array
    {
        if (empty($puntos)) return ['minX' => 0, 'maxX' => 0, 'minY' => 0, 'maxY' => 0, 'width' => 0, 'height' => 0];

        $minX = $maxX = $puntos[0]['x'];
        $minY = $maxY = $puntos[0]['y'];

        foreach ($puntos as $punto) {
            $minX = min($minX, $punto['x']);
            $maxX = max($maxX, $punto['x']);
            $minY = min($minY, $punto['y']);
            $maxY = max($maxY, $punto['y']);
        }

        return [
            'minX' => $minX, 'maxX' => $maxX,
            'minY' => $minY, 'maxY' => $maxY,
            'width' => max($maxX - $minX, 1),
            'height' => max($maxY - $minY, 1),
        ];
    }

    /**
     * Formatea las dimensiones de FerraWin a un texto legible para cotas.
     * Ej: "5 90d 320 90d 5" → "5+320+5"
     * Ej: "5 90d 25 90d 30 90d 25 90d 5" → "25×30 (estribo)"
     */
    public static function formatearCotasLegible(string $dimensiones): string
    {
        if (empty(trim($dimensiones))) return '';

        $tokens = preg_split('/\s+/', trim($dimensiones));
        $medidas = [];
        $angulos = 0;

        foreach ($tokens as $token) {
            $token = trim($token);
            if (empty($token)) continue;

            // Contar ángulos de 90 grados
            if (preg_match('/^[\d.]+d$/i', $token)) {
                $angulos++;
                continue;
            }

            // Extraer medidas numéricas (ignorar radios "r")
            if (is_numeric($token)) {
                $medidas[] = (int)$token;
            }
        }

        if (empty($medidas)) return '';

        // Si es un estribo (4+ ángulos de 90°), mostrar como ancho×alto
        if ($angulos >= 4 && count($medidas) >= 4) {
            // Típicamente: patilla + ancho + alto + ancho + patilla
            // Buscar las medidas centrales (ancho y alto)
            $ancho = $medidas[1] ?? $medidas[0];
            $alto = $medidas[2] ?? $medidas[1];
            return "{$ancho}×{$alto}";
        }

        // Para barras dobladas, mostrar todas las medidas separadas por "+"
        // Filtrar patillas muy pequeñas (< 10) al inicio/final si hay medidas grandes
        $medidasFiltradas = $medidas;
        if (count($medidas) > 2) {
            $maxMedida = max($medidas);
            // Si hay una medida central grande, las pequeñas son patillas
            if ($maxMedida > 50) {
                $medidasFiltradas = array_filter($medidas, function($m) use ($maxMedida) {
                    return $m > 10 || $m == $maxMedida;
                });
                $medidasFiltradas = array_values($medidasFiltradas);
            }
        }

        // Si solo hay una medida, es una barra recta
        if (count($medidasFiltradas) == 1) {
            return $medidasFiltradas[0] . '';
        }

        // Unir con "+" para barras dobladas
        return implode('+', $medidasFiltradas);
    }

    // Legacy methods
    public static function parsearDimensiones(string $dimensiones): array
    {
        return self::parsearDimensionesCompleto($dimensiones);
    }

    public static function renderizarSeccionFormas($composicion, $cotas, $armadura, $longitud): string
    {
        return '';
    }

    public static function renderizarElementosParaEtiqueta($elementosPorLetra, $opciones = []): string
    {
        return self::renderizarLeyenda(array_merge($opciones, ['elementosPorLetra' => $elementosPorLetra]));
    }

    // Métodos legacy para compatibilidad
    public static function renderizarVistaLongitudinal(array $opciones): string
    {
        return self::renderizarVista3D($opciones);
    }

    public static function renderizarSeccionTransversal(array $opciones): string
    {
        return self::renderizarSeccionDetallada($opciones);
    }

    public static function renderizarTablaElementos(array $opciones): string
    {
        return self::renderizarLeyenda($opciones);
    }
}
