<?php

namespace App\Helpers;

/**
 * Parser para decodificar el campo ZOBJETO de FerraWin
 * que contiene instrucciones de dibujo para elementos de ensamblaje.
 *
 * El formato ZOBJETO usa caracteres especiales como separadores:
 * - É (0xC389) → separador de campo (=)
 * - Ê (0xC38A) → separador de registro (|)
 */
class ZobjetoParser
{
    /**
     * Decodifica el campo ZOBJETO y extrae datos de dibujo
     */
    public static function parse(?string $zobjeto): array
    {
        if (empty($zobjeto)) {
            return [];
        }

        // Reemplazar caracteres especiales por separadores legibles
        $decoded = str_replace(
            ["\xC3\x89", "\xC3\x8A"],
            ['=', '|'],
            $zobjeto
        );

        $parts = explode('|', $decoded);
        $data = [
            'raw_parts' => [],
            'height' => null,      // H - altura total del dibujo
            'width' => null,       // G - anchura
            'segments' => [],      // Segmentos de la figura
            'hooks' => [],         // Ganchos/dobleces
            'coordinates' => [],   // Coordenadas J,I para puntos
        ];

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            // Extraer altura (H=xxx)
            if (preg_match('/H=(\d+)/', $part, $m)) {
                $data['height'] = (int)$m[1];
            }

            // Extraer anchura (G=xxx)
            if (preg_match('/G=(\d+)/', $part, $m)) {
                $data['width'] = (int)$m[1];
            }

            // Extraer segmentos Y=xxx (longitudes de segmentos)
            if (preg_match('/Y=(\d+)/', $part, $m)) {
                $data['segments'][] = (int)$m[1];
            }

            // Extraer tipo de gancho AT=xxx
            if (preg_match('/AT=(\w+)/', $part, $m)) {
                $data['hooks'][] = [
                    'type' => $m[1],
                    'angle' => self::extractHookAngle($m[1]),
                ];
            }

            // Extraer ángulo de doblez BZ=xxx
            if (preg_match('/BZ=(\d+)/', $part, $m)) {
                $lastHookIndex = count($data['hooks']) - 1;
                if ($lastHookIndex >= 0) {
                    $data['hooks'][$lastHookIndex]['bend_angle'] = (int)$m[1];
                }
            }

            // Extraer coordenadas J,I
            if (preg_match('/J=(-?\d+)/', $part, $mj)) {
                $coord = ['j' => (int)$mj[1]];
                if (preg_match('/I=(-?\d+)/', $part, $mi)) {
                    $coord['i'] = (int)$mi[1];
                }
                $data['coordinates'][] = $coord;
            } elseif (preg_match('/I=(-?\d+)/', $part, $mi)) {
                $data['coordinates'][] = ['i' => (int)$mi[1]];
            }

            // Extraer radio de arco AR=xxx
            if (preg_match('/AR=(\d+)/', $part, $m)) {
                $data['arc_radius'] = (int)$m[1];
            }

            // Guardar parte raw para debug
            if (preg_match('/[A-Z]+=/', $part)) {
                $data['raw_parts'][] = $part;
            }
        }

        // Limpiar raw_parts si no se necesita
        unset($data['raw_parts']);

        return $data;
    }

    /**
     * Extrae el ángulo de un tipo de gancho
     * Ej: HOOK90AG → 90°
     */
    protected static function extractHookAngle(string $hookType): ?int
    {
        if (preg_match('/HOOK(\d+)/', $hookType, $m)) {
            return (int)$m[1];
        }
        return null;
    }

    /**
     * Parsea el campo ZFIGURA que contiene dimensiones de la forma
     * Formato: "20\t90d\t1180" → [20, '90d', 1180]
     *
     * @return array Array de dimensiones
     */
    public static function parseFigura(?string $figura): array
    {
        if (empty($figura)) {
            return [];
        }

        $parts = preg_split('/[\t\s]+/', trim($figura));
        $dimensions = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            // Detectar si es un ángulo (ej: 90d, 135d)
            if (preg_match('/^(\d+)d$/', $part, $m)) {
                $dimensions[] = [
                    'type' => 'angle',
                    'value' => (int)$m[1],
                ];
            }
            // Detectar si es una longitud numérica
            elseif (is_numeric($part)) {
                $dimensions[] = [
                    'type' => 'length',
                    'value' => (float)$part,
                ];
            }
            // Otro tipo de valor
            else {
                $dimensions[] = [
                    'type' => 'unknown',
                    'value' => $part,
                ];
            }
        }

        return $dimensions;
    }

    /**
     * Combina datos de ZOBJETO y ZFIGURA para crear datos de dibujo completos
     */
    public static function buildDibujoData(?string $zobjeto, ?string $figura, array $elementData = []): array
    {
        $objData = self::parse($zobjeto);
        $figData = self::parseFigura($figura);

        return [
            'canvas' => [
                'height' => $objData['height'] ?? null,
                'width' => $objData['width'] ?? null,
            ],
            'segments' => $objData['segments'] ?? [],
            'hooks' => $objData['hooks'] ?? [],
            'coordinates' => $objData['coordinates'] ?? [],
            'arc_radius' => $objData['arc_radius'] ?? null,
            'figura' => $figData,
            // Datos adicionales del elemento
            'diametro' => $elementData['diametro'] ?? null,
            'cantidad' => $elementData['cantidad'] ?? null,
            'longitud' => $elementData['longitud'] ?? null,
            'dobleces' => $elementData['dobleces'] ?? null,
        ];
    }
}
