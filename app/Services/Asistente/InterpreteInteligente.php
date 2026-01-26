<?php

namespace App\Services\Asistente;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Sistema inteligente de interpretación de preguntas
 *
 * Combina múltiples técnicas:
 * 1. Detección de intención por patrones
 * 2. Extracción de entidades (máquinas, planillas, etc.)
 * 3. Consultas SQL predefinidas optimizadas
 * 4. Few-shot learning para casos complejos
 */
class InterpreteInteligente
{
    // Mapeo de máquinas conocidas
    private array $maquinas = [];

    // Intenciones reconocidas con sus patrones
    private array $intenciones = [
        'cola_maquina' => [
            'patrones' => [
                '/primera\s+planilla\s+(?:en\s+)?(?:la\s+)?(.+)/iu',
                '/cola\s+(?:de\s+)?(?:trabajo\s+)?(?:en\s+)?(?:la\s+)?(.+)/iu',
                '/siguiente\s+planilla\s+(?:en\s+)?(?:la\s+)?(.+)/iu',
                '/qu[eé]\s+(?:hay|toca|sigue)\s+(?:en\s+)?(?:la\s+)?(.+)/iu',
                '/planillas?\s+(?:en\s+)?(?:la\s+)?(.+)/iu',
            ],
            'requiere_maquina' => true,
        ],
        'kilos_pendientes' => [
            'patrones' => [
                '/(?:cu[aá]ntos?\s+)?kilos?\s+(?:pendientes?|por\s+hacer|faltan?)\s*(?:en\s+)?(?:la\s+)?(.+)?/iu',
                '/(?:cu[aá]nto\s+)?peso\s+(?:pendiente|por\s+hacer|falta)\s*(?:en\s+)?(?:la\s+)?(.+)?/iu',
                '/(?:qu[eé]\s+)?(?:queda|falta)\s+(?:por\s+)?(?:hacer|fabricar)\s*(?:en\s+)?(?:la\s+)?(.+)?/iu',
            ],
            'requiere_maquina' => false,
        ],
        'kilos_fabricados' => [
            'patrones' => [
                '/(?:cu[aá]ntos?\s+)?kilos?\s+(?:fabricados?|hechos?|terminados?)\s*(?:hoy|ayer|esta\s+semana)?\s*(?:en\s+)?(?:la\s+)?(.+)?/iu',
                '/(?:cu[aá]nto\s+)?(?:llevo|llevamos|hemos?\s+hecho)\s*(?:en\s+)?(?:la\s+)?(.+)?/iu',
                '/producci[oó]n\s+(?:de\s+)?(?:hoy|ayer|del\s+d[ií]a)\s*(?:en\s+)?(?:la\s+)?(.+)?/iu',
            ],
            'requiere_maquina' => false,
        ],
        'salidas_hoy' => [
            'patrones' => [
                '/(?:qu[eé]\s+)?sale\s+hoy/iu',
                '/salidas?\s+(?:de\s+)?hoy/iu',
                '/portes?\s+(?:de\s+)?hoy/iu',
                '/(?:qu[eé]\s+)?(?:hay\s+)?(?:que\s+)?cargar\s+hoy/iu',
            ],
            'requiere_maquina' => false,
        ],
        'stock_diametro' => [
            'patrones' => [
                '/stock\s+(?:de(?:l)?\s+)?(?:di[aá]metro\s+)?(?:Ø)?(\d+)/iu',
                '/(?:cu[aá]nto\s+)?(?:hay|tenemos)\s+(?:de(?:l)?\s+)?(?:Ø)?(\d+)/iu',
                '/material\s+(?:de(?:l)?\s+)?(?:Ø)?(\d+)/iu',
            ],
            'requiere_maquina' => false,
        ],
        'planilla_info' => [
            'patrones' => [
                '/(?:info(?:rmaci[oó]n)?\s+(?:de\s+)?(?:la\s+)?)?planilla\s+([A-Z0-9\-]+)/iu',
                '/([A-Z0-9\-]+)\s+(?:c[oó]mo\s+(?:va|est[aá])|estado|info)/iu',
            ],
            'requiere_maquina' => false,
        ],
        'saludo' => [
            'patrones' => [
                '/^hola\b/iu',
                '/^buenos?\s+(?:d[ií]as?|tardes?|noches?)/iu',
                '/^qu[eé]\s+tal/iu',
                '/^hey\b/iu',
            ],
            'requiere_maquina' => false,
        ],
    ];

    public function __construct()
    {
        $this->cargarMaquinas();
    }

    /**
     * Carga el mapeo de máquinas desde la BD (cacheado)
     */
    private function cargarMaquinas(): void
    {
        $this->maquinas = Cache::remember('interprete_maquinas', 3600, function () {
            $maquinas = DB::table('maquinas')
                ->select('id', 'nombre', 'codigo', 'tipo')
                ->get();

            $mapeo = [];
            foreach ($maquinas as $m) {
                // Indexar por diferentes formas de referirse a la máquina
                $nombreLower = mb_strtolower($m->nombre);
                $codigoLower = mb_strtolower($m->codigo ?? '');

                $mapeo[$nombreLower] = $m->id;
                if ($codigoLower) {
                    $mapeo[$codigoLower] = $m->id;
                }

                // Variantes comunes
                // "syntax line 28" -> "syntax", "line 28", "sl28"
                if (preg_match('/syntax\s*line\s*(\d+)/i', $m->nombre, $matches)) {
                    $mapeo['syntax line ' . $matches[1]] = $m->id;
                    $mapeo['syntax' . $matches[1]] = $m->id;
                    $mapeo['sl' . $matches[1]] = $m->id;
                    $mapeo['line ' . $matches[1]] = $m->id;
                }
                // "mini syntax 16" -> "mini syntax", "ms16"
                if (preg_match('/mini\s*syntax\s*(\d+)/i', $m->nombre, $matches)) {
                    $mapeo['mini syntax ' . $matches[1]] = $m->id;
                    $mapeo['mini syntax'] = $m->id;
                    $mapeo['ms' . $matches[1]] = $m->id;
                }
                // MSR
                if (stripos($m->nombre, 'msr') !== false) {
                    preg_match('/msr\s*(\d+)?/i', $m->nombre, $matches);
                    $mapeo['msr'] = $m->id;
                    if (!empty($matches[1])) {
                        $mapeo['msr' . $matches[1]] = $m->id;
                        $mapeo['msr ' . $matches[1]] = $m->id;
                    }
                }
            }

            return $mapeo;
        });
    }

    /**
     * Interpreta una pregunta y devuelve la intención y entidades detectadas
     */
    public function interpretar(string $pregunta): array
    {
        $pregunta = trim($pregunta);
        $preguntaLower = mb_strtolower($pregunta);

        // Detectar intención
        foreach ($this->intenciones as $intencion => $config) {
            foreach ($config['patrones'] as $patron) {
                if (preg_match($patron, $pregunta, $matches)) {
                    $entidades = $this->extraerEntidades($preguntaLower, $matches);

                    // Si requiere máquina y no se encontró, no matchea
                    if ($config['requiere_maquina'] && empty($entidades['maquina_id'])) {
                        continue;
                    }

                    return [
                        'detectada' => true,
                        'intencion' => $intencion,
                        'entidades' => $entidades,
                        'pregunta_original' => $pregunta,
                    ];
                }
            }
        }

        // No se detectó intención específica
        return [
            'detectada' => false,
            'intencion' => null,
            'entidades' => $this->extraerEntidades($preguntaLower, []),
            'pregunta_original' => $pregunta,
        ];
    }

    /**
     * Extrae entidades de la pregunta
     */
    private function extraerEntidades(string $preguntaLower, array $matches): array
    {
        $entidades = [
            'maquina_id' => null,
            'maquina_nombre' => null,
            'planilla_codigo' => null,
            'diametro' => null,
            'fecha' => null,
            'periodo' => 'hoy', // default
        ];

        // Extraer máquina del match o de la pregunta completa
        $textoMaquina = $matches[1] ?? $preguntaLower;
        $textoMaquina = mb_strtolower(trim($textoMaquina));

        // Limpiar texto de palabras comunes
        $textoMaquina = preg_replace('/\b(en|la|el|de|del|los|las|una?|por|para|con)\b/i', '', $textoMaquina);
        $textoMaquina = trim(preg_replace('/\s+/', ' ', $textoMaquina));

        // Buscar máquina
        foreach ($this->maquinas as $nombre => $id) {
            if (stripos($textoMaquina, $nombre) !== false ||
                stripos($preguntaLower, $nombre) !== false) {
                $entidades['maquina_id'] = $id;
                $entidades['maquina_nombre'] = $nombre;
                break;
            }
        }

        // Extraer diámetro
        if (preg_match('/(?:Ø|di[aá]metro\s*)?(\d{1,2})(?:\s*mm)?/i', $preguntaLower, $m)) {
            $d = (int)$m[1];
            if ($d >= 6 && $d <= 40) { // Diámetros válidos de ferralla
                $entidades['diametro'] = $d;
            }
        }

        // Extraer código de planilla
        if (preg_match('/\b(\d{4}-\d{4,6})\b/', $preguntaLower, $m)) {
            $entidades['planilla_codigo'] = $m[1];
        }

        // Extraer periodo temporal
        if (preg_match('/\bayer\b/i', $preguntaLower)) {
            $entidades['periodo'] = 'ayer';
            $entidades['fecha'] = date('Y-m-d', strtotime('-1 day'));
        } elseif (preg_match('/\bhoy\b/i', $preguntaLower)) {
            $entidades['periodo'] = 'hoy';
            $entidades['fecha'] = date('Y-m-d');
        } elseif (preg_match('/\besta\s+semana\b/i', $preguntaLower)) {
            $entidades['periodo'] = 'semana';
        } elseif (preg_match('/\beste\s+mes\b/i', $preguntaLower)) {
            $entidades['periodo'] = 'mes';
        }

        return $entidades;
    }

    /**
     * Genera la consulta SQL optimizada para una intención
     */
    public function generarSQL(string $intencion, array $entidades): ?array
    {
        switch ($intencion) {
            case 'cola_maquina':
                return $this->sqlColaMaquina($entidades);

            case 'kilos_pendientes':
                return $this->sqlKilosPendientes($entidades);

            case 'kilos_fabricados':
                return $this->sqlKilosFabricados($entidades);

            case 'salidas_hoy':
                return $this->sqlSalidasHoy();

            case 'stock_diametro':
                return $this->sqlStockDiametro($entidades);

            case 'planilla_info':
                return $this->sqlPlanillaInfo($entidades);

            default:
                return null;
        }
    }

    /**
     * Genera respuesta para saludo
     */
    public function respuestaSaludo(): string
    {
        $saludos = [
            "¡Hola! 👋 Soy **Ferrallin**, tu asistente. ¿Qué necesitas?",
            "¡Buenas! ¿En qué puedo ayudarte?",
            "¡Hola! Aquí estoy para ayudarte. ¿Qué quieres saber?",
        ];
        return $saludos[array_rand($saludos)];
    }

    // ============ CONSULTAS SQL OPTIMIZADAS ============

    private function sqlColaMaquina(array $entidades): array
    {
        $maquinaId = $entidades['maquina_id'];

        return [
            'sql' => "SELECT p.codigo, c.empresa as cliente, o.obra, op.posicion
                      FROM orden_planillas op
                      JOIN planillas p ON op.planilla_id = p.id
                      JOIN obras o ON p.obra_id = o.id
                      JOIN clientes c ON o.cliente_id = c.id
                      WHERE op.maquina_id = {$maquinaId}
                      ORDER BY op.posicion ASC
                      LIMIT 10",
            'explicacion' => "Cola de trabajo de la máquina",
            'formato' => 'cola',
        ];
    }

    private function sqlKilosPendientes(array $entidades): array
    {
        $where = "et.estado IN ('pendiente', 'en_proceso') AND e.deleted_at IS NULL";

        if (!empty($entidades['maquina_id'])) {
            $where .= " AND e.maquina_id = {$entidades['maquina_id']}";
        }

        return [
            'sql' => "SELECT
                        COALESCE(SUM(e.peso), 0) as kilos_pendientes,
                        COUNT(*) as elementos_pendientes
                      FROM elementos e
                      JOIN etiquetas et ON e.etiqueta_sub_id = et.etiqueta_sub_id
                      WHERE {$where}",
            'explicacion' => "Kilos pendientes de fabricar",
            'formato' => 'cantidad',
        ];
    }

    private function sqlKilosFabricados(array $entidades): array
    {
        $where = "et.estado IN ('fabricado', 'completada') AND e.deleted_at IS NULL";

        if (!empty($entidades['maquina_id'])) {
            $where .= " AND e.maquina_id = {$entidades['maquina_id']}";
        }

        // Filtro temporal
        switch ($entidades['periodo'] ?? 'hoy') {
            case 'ayer':
                $where .= " AND DATE(et.updated_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'semana':
                $where .= " AND YEARWEEK(et.updated_at, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'mes':
                $where .= " AND MONTH(et.updated_at) = MONTH(CURDATE()) AND YEAR(et.updated_at) = YEAR(CURDATE())";
                break;
            default: // hoy
                $where .= " AND DATE(et.updated_at) = CURDATE()";
        }

        return [
            'sql' => "SELECT
                        COALESCE(SUM(e.peso), 0) as kilos_fabricados,
                        COUNT(*) as elementos_fabricados
                      FROM elementos e
                      JOIN etiquetas et ON e.etiqueta_sub_id = et.etiqueta_sub_id
                      WHERE {$where}",
            'explicacion' => "Kilos fabricados",
            'formato' => 'cantidad',
        ];
    }

    private function sqlSalidasHoy(): array
    {
        return [
            'sql' => "SELECT sa.codigo, sa.fecha, sa.estado,
                        o.obra, c.empresa as cliente,
                        u.name as camionero
                      FROM salidas_almacen sa
                      LEFT JOIN obras o ON sa.obra_id = o.id
                      LEFT JOIN clientes c ON o.cliente_id = c.id
                      LEFT JOIN users u ON sa.camionero_id = u.id
                      WHERE DATE(sa.fecha) = CURDATE()
                      ORDER BY sa.fecha ASC",
            'explicacion' => "Salidas programadas para hoy",
            'formato' => 'lista',
        ];
    }

    private function sqlStockDiametro(array $entidades): array
    {
        $diametro = $entidades['diametro'] ?? 12;

        return [
            'sql' => "SELECT
                        p.nombre, p.diametro,
                        COALESCE(p.peso_stock, 0) as stock_kg,
                        u.nombre as ubicacion
                      FROM productos p
                      LEFT JOIN ubicaciones u ON p.ubicacion_id = u.id
                      WHERE p.diametro = {$diametro}
                      AND p.peso_stock > 0
                      ORDER BY p.peso_stock DESC",
            'explicacion' => "Stock del diámetro {$diametro}",
            'formato' => 'lista',
        ];
    }

    private function sqlPlanillaInfo(array $entidades): array
    {
        $codigo = $entidades['planilla_codigo'] ?? '';

        return [
            'sql' => "SELECT p.codigo, p.estado, p.revisada,
                        c.empresa as cliente, o.obra,
                        p.peso_total, p.fecha_estimada_entrega,
                        (SELECT COUNT(*) FROM elementos e JOIN etiquetas et ON e.etiqueta_sub_id = et.etiqueta_sub_id WHERE e.planilla_id = p.id AND et.estado IN ('pendiente', 'en_proceso') AND e.deleted_at IS NULL) as elementos_pendientes,
                        (SELECT COUNT(*) FROM elementos e JOIN etiquetas et ON e.etiqueta_sub_id = et.etiqueta_sub_id WHERE e.planilla_id = p.id AND et.estado IN ('fabricado', 'completada') AND e.deleted_at IS NULL) as elementos_fabricados
                      FROM planillas p
                      JOIN obras o ON p.obra_id = o.id
                      JOIN clientes c ON o.cliente_id = c.id
                      WHERE p.codigo LIKE '%{$codigo}%'
                      LIMIT 1",
            'explicacion' => "Información de planilla",
            'formato' => 'detalle',
        ];
    }

    /**
     * Formatea la respuesta según el tipo de datos
     */
    public function formatearRespuesta(string $intencion, array $entidades, array $datos): string
    {
        if (empty($datos)) {
            return $this->respuestaVacia($intencion, $entidades);
        }

        switch ($intencion) {
            case 'cola_maquina':
                return $this->formatearCola($entidades, $datos);

            case 'kilos_pendientes':
                return $this->formatearKilosPendientes($entidades, $datos);

            case 'kilos_fabricados':
                return $this->formatearKilosFabricados($entidades, $datos);

            case 'salidas_hoy':
                return $this->formatearSalidas($datos);

            case 'stock_diametro':
                return $this->formatearStock($entidades, $datos);

            case 'planilla_info':
                return $this->formatearPlanillaInfo($datos);

            default:
                return "Encontré " . count($datos) . " resultados.";
        }
    }

    private function respuestaVacia(string $intencion, array $entidades): string
    {
        $maquina = $entidades['maquina_nombre'] ?? 'esa máquina';

        switch ($intencion) {
            case 'cola_maquina':
                return "No hay planillas en cola para **{$maquina}**. La cola está vacía.";
            case 'salidas_hoy':
                return "No hay salidas programadas para hoy.";
            default:
                return "No encontré resultados.";
        }
    }

    private function formatearCola(array $entidades, array $datos): string
    {
        $maquina = ucwords($entidades['maquina_nombre'] ?? 'la máquina');
        $primera = $datos[0];

        $respuesta = "La primera planilla en **{$maquina}** es la **{$primera->codigo}** ";
        $respuesta .= "de **{$primera->cliente}** para la obra **{$primera->obra}**.";

        if (count($datos) > 1) {
            $respuesta .= "\n\n**Cola completa** (" . count($datos) . " planillas):\n";
            foreach ($datos as $i => $p) {
                $pos = $i + 1;
                $respuesta .= "{$pos}. **{$p->codigo}** - {$p->cliente} / {$p->obra}\n";
            }
        }

        return $respuesta;
    }

    private function formatearKilosPendientes(array $entidades, array $datos): string
    {
        $dato = $datos[0];
        $kilos = number_format($dato->kilos_pendientes, 0, ',', '.');
        $elementos = number_format($dato->elementos_pendientes, 0, ',', '.');

        $maquina = $entidades['maquina_nombre'] ?? null;

        if ($maquina) {
            return "En **" . ucwords($maquina) . "** hay **{$kilos} kg** pendientes ({$elementos} elementos).";
        }

        return "Hay **{$kilos} kg** pendientes en total ({$elementos} elementos).";
    }

    private function formatearKilosFabricados(array $entidades, array $datos): string
    {
        $dato = $datos[0];
        $kilos = number_format($dato->kilos_fabricados, 0, ',', '.');
        $elementos = number_format($dato->elementos_fabricados, 0, ',', '.');

        $periodo = match($entidades['periodo'] ?? 'hoy') {
            'ayer' => 'ayer',
            'semana' => 'esta semana',
            'mes' => 'este mes',
            default => 'hoy',
        };

        $maquina = $entidades['maquina_nombre'] ?? null;

        if ($maquina) {
            return "En **" . ucwords($maquina) . "** se han fabricado **{$kilos} kg** {$periodo} ({$elementos} elementos).";
        }

        return "Se han fabricado **{$kilos} kg** {$periodo} ({$elementos} elementos).";
    }

    private function formatearSalidas(array $datos): string
    {
        $total = count($datos);
        $respuesta = "Hay **{$total} salida(s)** programadas para hoy:\n\n";

        foreach ($datos as $s) {
            $hora = date('H:i', strtotime($s->fecha));
            $estado = match($s->estado) {
                'pendiente' => '⏳',
                'en_transito' => '🚚',
                'entregada' => '✅',
                default => '📦',
            };
            $respuesta .= "{$estado} **{$s->codigo}** ({$hora}) - {$s->cliente} / {$s->obra}";
            if ($s->camionero) {
                $respuesta .= " - Camionero: {$s->camionero}";
            }
            $respuesta .= "\n";
        }

        return $respuesta;
    }

    private function formatearStock(array $entidades, array $datos): string
    {
        $diametro = $entidades['diametro'] ?? '?';
        $totalKg = array_sum(array_column($datos, 'stock_kg'));
        $totalKgFmt = number_format($totalKg, 0, ',', '.');

        $respuesta = "Stock de **Ø{$diametro}**: **{$totalKgFmt} kg** total\n\n";

        foreach ($datos as $p) {
            $kg = number_format($p->stock_kg, 0, ',', '.');
            $ubicacion = $p->ubicacion ?? 'Sin ubicación';
            $respuesta .= "• {$p->nombre}: **{$kg} kg** ({$ubicacion})\n";
        }

        return $respuesta;
    }

    private function formatearPlanillaInfo(array $datos): string
    {
        $p = $datos[0];

        $estado = match($p->estado) {
            'pendiente' => '⏳ Pendiente',
            'fabricando' => '🔧 Fabricando',
            'completada' => '✅ Completada',
            default => $p->estado,
        };

        $revisada = $p->revisada ? '✅ Sí' : '❌ No';
        $peso = number_format($p->peso_total ?? 0, 2, ',', '.') . ' kg';
        $entrega = $p->fecha_estimada_entrega ? date('d/m/Y', strtotime($p->fecha_estimada_entrega)) : 'Sin fecha';

        return "**Planilla {$p->codigo}**\n\n" .
               "• Cliente: **{$p->cliente}**\n" .
               "• Obra: **{$p->obra}**\n" .
               "• Estado: {$estado}\n" .
               "• Revisada: {$revisada}\n" .
               "• Peso total: {$peso}\n" .
               "• Entrega estimada: {$entrega}\n" .
               "• Elementos: {$p->elementos_fabricados} fabricados / {$p->elementos_pendientes} pendientes";
    }
}
