<?php

namespace App\Http\Controllers;

use App\Models\Distribuidor;
use App\Models\DistribuidorDireccion;
use App\Models\PedidoProducto;
use App\Models\PedidoProductoColada;
use App\Models\IAAprendizajePrioridad;
use App\Models\Entrada;
use App\Models\Producto;
use App\Models\Movimiento;
use App\Models\Colada;
use App\Models\Obra;
use App\Models\EntradaImportLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\AlbaranOcrService;
use App\Services\PrioridadIAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AlbaranesScanController extends Controller
{
    public function index()
    {
        $distribuidores = Distribuidor::query()
            ->pluck('nombre')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $fabricantes = \App\Models\Fabricante::query()
            ->pluck('nombre')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $naves = Obra::query()
            ->whereIn('obra', ['Nave A', 'Nave B'])
            ->pluck('id', 'obra')
            ->toArray();

        return view('albaranes.scan', [
            'distribuidores' => $distribuidores,
            'fabricantes' => $fabricantes,
            'naves' => $naves,
        ]);
    }

    public function procesar(Request $request, AlbaranOcrService $service)
    {
        $request->validate([
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
            'proveedor' => 'nullable|string|in:siderurgica,megasa,balboa,otro',
        ]);

        $proveedor = $request->input('proveedor');

        $distribuidores = Distribuidor::query()
            ->pluck('nombre')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $naves = Obra::query()
            ->whereIn('obra', ['Nave A', 'Nave B'])
            ->pluck('id', 'obra')
            ->toArray();

        $resultados = [];

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                try {
                    $log = $service->parseAndLog($imagen, Auth::id(), $proveedor);
                    $parsed = $log->parsed_payload ?? [];
                    $statusMessages = $parsed['_ai_status'] ?? [];
                    $aiMeta = $parsed['_ai_meta'] ?? [];
                    unset($parsed['_ai_status'], $parsed['_ai_meta']);

                    // Generar preview base64 para mostrar al usuario
                    $previewData = null;
                    $mime = $imagen->getMimeType();
                    try {
                        $content = Storage::disk('private')->get($log->file_path);
                        $previewData = 'data:' . $mime . ';base64,' . base64_encode($content);
                    } catch (\Throwable $e) {
                        $previewData = null;
                    }

                    // Buscar líneas pendientes y generar simulación
                    $simulacion = $this->generarSimulacion($parsed);
                    $parsed['bultos_total'] = $simulacion['bultos_albaran'];
                    $parsed['peso_total'] = $simulacion['peso_total'];
                    $parsed['tipo_compra'] = isset($parsed['tipo_compra']) ? mb_strtolower($parsed['tipo_compra']) : null;

                    // IA Inteligente para Distribuidor
                    $iaResult = $this->obtenerDistribuidorIA($parsed, $log->raw_text ?? '', $distribuidores);
                    $distribuidorRecomendado = $iaResult['recomendacion'] ?? null;
                    $iaDebug = $iaResult['debug'] ?? null;

                    // Fallback
                    if (!$distribuidorRecomendado) {
                        $distribuidorRecomendado = $this->determinarDistribuidorRecomendado(
                            $parsed['tipo_compra'],
                            $parsed['proveedor_texto'] ?? null,
                            $distribuidores
                        );
                    }

                    $parsed['distribuidor_recomendado'] = $distribuidorRecomendado;

                    $resultados[] = [
                        'ocr_log_id' => $log->id,
                        'nombre_archivo' => $imagen->getClientOriginalName(),
                        'preview' => $previewData,
                        'parsed' => $parsed,
                        'raw' => $log->raw_text,
                        'simulacion' => $simulacion,
                        'status_messages' => $statusMessages,
                        'ai_meta' => $aiMeta,
                        'ia_debug' => $iaDebug, // Nueva key
                        'error' => null,
                    ];

                } catch (\Exception $e) {
                    Log::error('Error procesando imagen con OpenAI: ' . $e->getMessage());
                    $resultados[] = [
                        'nombre_archivo' => $imagen->getClientOriginalName(),
                        'preview' => null,
                        'parsed' => null,
                        'raw' => null,
                        'ocr_log_id' => null,
                        'error' => 'Error al procesar: ' . $e->getMessage(),
                    ];
                }
            }
        }

        return view('albaranes.scan', [
            'resultados' => $resultados,
            'proveedor' => $proveedor,
            'distribuidores' => $distribuidores,
            'naves' => $naves,
        ]);
    }

    /**
     * Procesar albarán vía AJAX para vista móvil
     * Retorna JSON en lugar de vista
     */
    public function procesarAjax(Request $request, AlbaranOcrService $service)
    {
        $request->validate([
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
            'proveedor' => 'nullable|string|in:siderurgica,megasa,balboa,otro',
        ]);

        $proveedor = $request->input('proveedor');

        $distribuidores = Distribuidor::query()
            ->pluck('nombre')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $resultados = [];

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                try {
                    $log = $service->parseAndLog($imagen, Auth::id(), $proveedor);
                    $rawParsed = $log->parsed_payload ?? [];

                    // Asegurar que usamos 'data' si existe, o el root si no
                    $parsed = $rawParsed['data'] ?? $rawParsed;

                    // Si hay claves en el root que no están en 'data', fusionarlas (por compatibilidad)
                    if (isset($rawParsed['data']) && is_array($rawParsed['data'])) {
                        $parsed = array_merge($rawParsed, $rawParsed['data']);
                    }

                    $statusMessages = $rawParsed['_ai_status'] ?? [];
                    $aiMeta = $rawParsed['_ai_meta'] ?? [];
                    unset($parsed['_ai_status'], $parsed['_ai_meta']);

                    // Generar preview base64 para mostrar al usuario
                    $previewData = null;
                    $mime = $imagen->getMimeType();
                    try {
                        $content = Storage::disk('private')->get($log->file_path);
                        $previewData = 'data:' . $mime . ';base64,' . base64_encode($content);
                    } catch (\Throwable $e) {
                        $previewData = null;
                    }

                    // Buscar líneas pendientes y generar simulación
                    $simulacion = $this->generarSimulacion($parsed);

                    // Asegurar que totales vengan del bloque 'data' si es posible
                    $parsed['bultos_total'] = $parsed['bultosTotal'] ?? $parsed['bultos_total'] ?? $simulacion['bultos_albaran'];
                    $parsed['peso_total'] = $parsed['pesoTotal'] ?? $parsed['peso_total'] ?? $simulacion['peso_total'];

                    // Normalizar tipo de compra
                    $parsed['tipo_compra'] = isset($parsed['tipoCompra'])
                        ? mb_strtolower($parsed['tipoCompra'])
                        : (isset($parsed['tipo_compra']) ? mb_strtolower($parsed['tipo_compra']) : null);

                    // IA Inteligente para Distribuidor (SOLO SI es distribuidor)
                    $distribuidorRecomendado = null;
                    $iaDebug = null;
                    if ($parsed['tipo_compra'] === 'distribuidor') {
                        $iaResult = $this->obtenerDistribuidorIA($parsed, $log->raw_text ?? '', $distribuidores);
                        $distribuidorRecomendado = $iaResult['recomendacion'] ?? null;
                        $iaDebug = $iaResult['debug'] ?? null;
                    }

                    // Fallback para distribuidor
                    if (!$distribuidorRecomendado && $parsed['tipo_compra'] === 'distribuidor') {
                        $distribuidorRecomendado = $this->determinarDistribuidorRecomendado(
                            $parsed['tipo_compra'],
                            $parsed['proveedorTexto'] ?? $parsed['proveedor_texto'] ?? null,
                            $distribuidores
                        );
                    }

                    // Si es directo, asegurarnos de que no haya distribuidor recomendado basura
                    if ($parsed['tipo_compra'] === 'directo') {
                        $distribuidorRecomendado = null;
                        $iaDebug = null; // No queremos ensuciar el log si al final no aplica
                    }

                    $parsed['distribuidor_recomendado'] = $distribuidorRecomendado;

                    // Normalizar productos para la vista (camelCase a snake_case si hace falta)
                    if (isset($parsed['productos']) && is_array($parsed['productos'])) {
                        foreach ($parsed['productos'] as &$p) {
                            if (isset($p['lineItems'])) {
                                $p['line_items'] = $p['lineItems'];
                                // unset($p['lineItems']); // Opcional, mantener ambas por si acaso
                            }
                            // Normalizar items internos
                            if (isset($p['line_items']) && is_array($p['line_items'])) {
                                foreach ($p['line_items'] as &$item) {
                                    if (isset($item['pesoNeto']))
                                        $item['peso_kg'] = $item['pesoNeto'];
                                }
                            }
                        }
                    }

                    $resultados[] = [
                        'ocr_log_id' => $log->id,
                        'nombre_archivo' => $imagen->getClientOriginalName(),
                        'preview' => $previewData,
                        'parsed' => $parsed,
                        'raw' => $log->raw_text,
                        'simulacion' => $simulacion,
                        'status_messages' => $statusMessages,
                        'ai_meta' => $aiMeta,
                        'ia_debug' => $iaDebug, // Nueva key
                        'error' => null,
                    ];

                } catch (\Exception $e) {
                    Log::error('Error procesando imagen con OpenAI: ' . $e->getMessage());
                    $resultados[] = [
                        'nombre_archivo' => $imagen->getClientOriginalName(),
                        'preview' => null,
                        'parsed' => null,
                        'raw' => null,
                        'ocr_log_id' => null,
                        'error' => 'Error al procesar: ' . $e->getMessage(),
                    ];
                }
            }
        }

        // RETORNAR JSON en lugar de vista
        return response()->json([
            'success' => true,
            'resultados' => $resultados,
            'distribuidores' => $distribuidores,
        ]);
    }

    /**
     * Busca líneas (pedido_productos) cuyo pedido padre contenga el código proporcionado
     * (ignora espacios y mayúsculas/minúsculas).
     */
    public function buscarPedido(Request $request)
    {
        $request->validate([
            'codigo' => 'nullable|string|max:255',
            'diametros' => 'nullable|array',
            'diametros.*' => 'nullable|numeric',
        ]);

        $codigo = (string) $request->input('codigo', '');
        $normalizeCode = fn($c) => preg_replace('/\s+/', '', mb_strtolower($c ?? ''));
        $normalized = $normalizeCode($codigo);

        $isCodeMatch = function ($scannedCode, $dbCode) use ($normalizeCode) {
            $s = $normalizeCode($scannedCode);
            $d = $normalizeCode($dbCode);
            if (!$s || !$d)
                return false;
            return str_contains($s, $d) || str_contains($d, $s);
        };

        $diametros = collect($request->input('diametros', []))
            ->filter(fn($v) => $v !== null && $v !== '')
            ->map(fn($v) => (float) $v)
            ->unique()
            ->values()
            ->all();
        $diametrosInt = array_values(array_unique(array_map(fn($v) => (int) round((float) $v), $diametros)));

        if ($normalized === '') {
            return response()->json([
                'exists' => false,
                'reason' => 'empty',
                'searched' => $codigo,
            ]);
        }

        $pedidoExpr = "LOWER(REPLACE(codigo, ' ', ''))";
        $baseWith = ['pedido.fabricante', 'pedido.distribuidor', 'productoBase', 'obra'];

        // Búsqueda eficiente por código (exacto o contenido)
        $lineas = PedidoProducto::query()
            ->with($baseWith)
            ->whereHas('pedido', function ($q) use ($normalized, $pedidoExpr) {
                $q->where(function ($subQ) use ($normalized, $pedidoExpr) {
                    $subQ->whereRaw("{$pedidoExpr} = ?", [$normalized])
                        ->orWhereRaw("{$pedidoExpr} LIKE ?", ['%' . $normalized . '%'])
                        ->orWhereRaw("? LIKE CONCAT('%', {$pedidoExpr}, '%')", [$normalized]);
                });
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $exactCount = $lineas->count(); // Simplificado para móvil
        $matchType = $lineas->isNotEmpty() ? 'exact' : 'none';
        $containsCount = 0;

        if ($lineas->isEmpty()) {
            return response()->json([
                'exists' => false,
                'reason' => 'not_found',
                'searched' => $codigo,
                'normalized_search' => $normalized,
                'match_type' => $matchType,
                'exact_count' => $exactCount,
                'contains_count' => $containsCount,
            ]);
        }

        // Si el albaran trae diametros y ninguna linea coincide, no mostrar "encontrado".
        if (!empty($diametrosInt)) {
            $lineas = $lineas->filter(function (PedidoProducto $linea) use ($diametrosInt) {
                $diam = $linea->productoBase?->diametro;
                if ($diam === null) {
                    return false;
                }
                $diamInt = (int) round((float) $diam);
                return in_array($diamInt, $diametrosInt, true);
            })->values();

            if ($lineas->isEmpty()) {
                return response()->json([
                    'exists' => false,
                    'reason' => 'diametro_mismatch',
                    'searched' => $codigo,
                    'normalized_search' => $normalized,
                    'match_type' => $matchType,
                    'exact_count' => $exactCount,
                    'contains_count' => $containsCount,
                    'diametros' => $diametrosInt,
                ]);
            }
        }

        $estadoRank = function (?string $estado): int {
            $e = mb_strtolower(trim((string) $estado));
            // Menor es mejor (más "usable" para recepcionar)
            return match ($e) {
                'pendiente' => 0,
                'parcial' => 1,
                '' => 2,
                'completado', 'completada' => 10,
                'facturado', 'facturada' => 11,
                'cancelado', 'cancelada' => 12,
                default => 5,
            };
        };

        $bestLinea = $lineas
            ->sortBy(function (PedidoProducto $linea) use ($diametrosInt, $estadoRank) {
                $rank = $estadoRank($linea->estado);
                $diamInt = (int) round((float) ($linea->productoBase?->diametro ?? 0));
                $diamMatch = (!empty($diametrosInt) && $diamInt > 0 && in_array($diamInt, $diametrosInt, true)) ? 0 : 1;
                // Orden: estado "bueno" primero, luego match de diámetro, luego más reciente
                return [$rank, $diamMatch, -$linea->created_at?->timestamp];
            })
            ->first();

        $lineasPayload = $lineas
            ->take(10)
            ->map(function (PedidoProducto $linea) {
                $pedido = $linea->pedido;
                $fabricante = $pedido?->fabricante?->nombre;
                $distribuidor = $pedido?->distribuidor?->nombre;
                $diametro = $linea->productoBase?->diametro;
                $producto = $linea->productoBase?->nombre;

                return [
                    'id' => $linea->id,
                    'codigo_linea' => $linea->codigo,
                    'estado' => $linea->estado,
                    'cantidad' => $linea->cantidad,
                    'cantidad_recepcionada' => $linea->cantidad_recepcionada,
                    'pedido' => [
                        'id' => $pedido?->id,
                        'codigo' => $pedido?->codigo,
                        'estado' => $pedido?->estado,
                        'peso_total' => $pedido?->peso_total,
                        'fabricante' => $fabricante,
                        'distribuidor' => $distribuidor,
                    ],
                    'producto' => [
                        'diametro' => $diametro,
                        'nombre' => $producto,
                    ],
                    'obra' => $linea->obra?->obra ?? $linea->obra_manual ?? null,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'exists' => true,
            'searched' => $codigo,
            'normalized_search' => $normalized,
            'match_type' => $matchType,
            'exact_count' => $exactCount,
            'contains_count' => $containsCount,
            'diametros' => !empty($diametrosInt) ? $diametrosInt : $diametros,
            'best_linea_id' => $bestLinea?->id,
            'lineas' => $lineasPayload,
        ]);
    }

    /**
     * Recalcula la simulación de selección de línea de pedido usando los datos editados.
     */
    public function simular(Request $request)
    {
        $request->validate([
            'parsed' => 'required|array',
            'proveedor' => 'nullable|string|in:siderurgica,megasa,balboa,otro',
        ]);

        $parsed = (array) $request->input('parsed', []);
        $proveedor = $request->input('proveedor') ?? ($parsed['proveedor'] ?? null);
        if ($proveedor) {
            $parsed['proveedor'] = $proveedor;
        }

        $simulacion = $this->generarSimulacion($parsed);

        return response()->json([
            'success' => true,
            'simulacion' => $simulacion,
        ]);
    }

    /**
     * Genera una simulación sugiriendo qué línea de pedido de COMPRA activar
     */
    protected function generarSimulacion(array $parsed): array
    {
        // 1. NORMALIZACIÓN DE DATOS
        $source = (isset($parsed['data']) && is_array($parsed['data'])) ? $parsed['data'] : $parsed;

        Log::info('GenerarSimulacion - Input Params:', $source);

        $productos = $source['productos'] ?? ($source['products'] ?? []);
        $pedidoCodigo = $source['pedido_codigo'] ?? ($source['pedido_cliente'] ?? null);

        $tipoCompra = mb_strtolower($source['tipo_compra'] ?? 'directo');
        $nombreFabricante = $source['proveedor_texto'] ?? null;
        $nombreDistribuidor = $source['distribuidor_seleccionado'] ?? ($source['distribuidor_recomendado'] ?? null);
        $obraId = $source['obra_id'] ?? null;
        if (is_string($obraId) && trim($obraId) === '') {
            $obraId = null;
        }
        if (is_string($obraId) && is_numeric($obraId)) {
            $obraId = (int) $obraId;
        }

        // 2. RESOLVER IDs DE EMPRESA
        $fabricanteId = null;
        $distribuidorId = null;

        if ($tipoCompra === 'directo' && $nombreFabricante && $nombreFabricante !== 'otro') {
            $fab = \App\Models\Fabricante::where('nombre', $nombreFabricante)->first();
            if ($fab) {
                $fabricanteId = $fab->id;
                Log::info("Simulación: Fabricante encontrado ID: {$fabricanteId}");
            }
        } elseif ($tipoCompra === 'distribuidor' && $nombreDistribuidor && $nombreDistribuidor !== 'otro') {
            $dist = \App\Models\Distribuidor::where('nombre', $nombreDistribuidor)->first();
            if ($dist) {
                $distribuidorId = $dist->id;
                Log::info("Simulación: Distribuidor encontrado ID: {$distribuidorId}");
            }
        }

        // 3. IDENTIFICAR PRODUCTOS BASE y BUSCAR CANDIDATOS
        $candidatos = collect();
        $bultosTotal = 0;
        $pesoTotal = 0;

        foreach ((array) $productos as $idx => $prod) {
            if (!is_array($prod))
                continue;

            // Calcular totales
            $items = $prod['line_items'] ?? $prod['lineItems'] ?? [];
            foreach ($items as $item) {
                $bultosTotal += (float) ($item['bultos'] ?? 0);
                $pesoTotal += (float) ($item['peso_kg'] ?? ($item['pesoNeto'] ?? 0));
            }

            // Datos del producto
            $tipoRaw = mb_strtoupper($prod['descripcion'] ?? '');
            $diametro = $this->extractNumber($prod['diametro'] ?? null);
            $longitud = $this->extractNumber($prod['longitud'] ?? null);

            $tipoDb = '';
            if (str_contains($tipoRaw, 'ENCARRETADO') || str_contains($tipoRaw, 'ROLLO')) {
                $tipoDb = 'encarretado';
            } elseif (str_contains($tipoRaw, 'BARRA')) {
                $tipoDb = 'barra';
            }

            // Buscar IDs de ProductoBase
            $queryPb = \App\Models\ProductoBase::query();
            if ($tipoDb) {
                $queryPb->where('tipo', 'LIKE', "%{$tipoDb}%");
            }
            if ($diametro) {
                $queryPb->whereBetween('diametro', [$diametro - 0.1, $diametro + 0.1]);
            }
            if ($tipoDb === 'barra' && $longitud) {
                $queryPb->whereBetween('longitud', [$longitud - 0.05, $longitud + 0.05]);
            }
            $productoBasesIds = $queryPb->pluck('id')->toArray();

            if (empty($productoBasesIds)) {
                Log::warning("Simulación: No se encontró ProductoBase para [{$tipoDb}, d={$diametro}]");
                continue;
            }

            // Buscar Líneas de Pedido
            $queryLineas = \App\Models\PedidoProducto::query()
                ->whereIn('producto_base_id', $productoBasesIds)
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->with(['pedido.fabricante', 'pedido.distribuidor', 'productoBase', 'obra']);

            if ($obraId) {
                $queryLineas->where('obra_id', $obraId);
            }

            $queryLineas->whereHas('pedido', function ($q) use ($tipoCompra, $fabricanteId, $distribuidorId, $pedidoCodigo) {
                if ($tipoCompra === 'directo' && $fabricanteId) {
                    $q->where('fabricante_id', $fabricanteId);
                } elseif ($tipoCompra === 'distribuidor' && $distribuidorId) {
                    $q->where('distribuidor_id', $distribuidorId);
                }
            });

            $lineasEncontradas = $queryLineas->get();

            foreach ($lineasEncontradas as $linea) {
                $score = 100;
                $linea->razones = [];

                // Score calculation removed - using Date Sorting
                $pendiente = $linea->cantidad - $linea->cantidad_recepcionada;

                // Formatear para frontend
                $lineaFmt = $linea->toArray();
                $lineaFmt['producto'] = $linea->productoBase->descripcion ?? $linea->productoBase->tipo . ' Ø' . $linea->productoBase->diametro;
                $lineaFmt['pedido_codigo'] = $linea->pedido->codigo ?? '—';
                $lineaFmt['fabricante'] = $linea->pedido->fabricante->nombre ?? null;
                $lineaFmt['distribuidor'] = $linea->pedido->distribuidor->nombre ?? null;
                $lineaFmt['obra'] = $linea->obra?->obra ?? ($linea->obra_manual ?? '—');

                // Fecha de entrega para ordenamiento
                $fechaEntrega = $linea->fecha_estimada_entrega ?? $linea->pedido->fecha_estimada_entrega ?? null;
                $lineaFmt['fecha_estimada_entrega'] = $fechaEntrega;
                $lineaFmt['fecha_entrega'] = $fechaEntrega;

                $lineaFmt['score'] = $score;
                $lineaFmt['cantidad_pendiente'] = $pendiente;

                $candidatos->push($lineaFmt);
            }
        }

        // 4. SELECCIÓN MEJOR CANDIDATO (Por Fecha Entrega Ascendente)
        $candidatosSorted = $candidatos->sortBy(function ($v) {
            return $v['fecha_estimada_entrega'] ?? '9999-12-31';
        })->values();
        $mejorCandidato = $candidatosSorted->first();

        $datosLinea = $mejorCandidato;
        $estadoFinalSimulado = null;

        if ($datosLinea) {
            $nuevaCantidadRecepcionada = ($datosLinea['cantidad_recepcionada'] ?? 0) + $pesoTotal;
            $nuevoEstado = $nuevaCantidadRecepcionada >= ($datosLinea['cantidad'] ?? 0) ? 'completado' : 'parcial';

            $estadoFinalSimulado = [
                'cantidad_recepcionada_nueva' => $nuevaCantidadRecepcionada,
                'cantidad_total' => $datosLinea['cantidad'],
                'estado_nuevo' => $nuevoEstado,
                'progreso' => ($datosLinea['cantidad'] > 0) ? round(($nuevaCantidadRecepcionada / $datosLinea['cantidad']) * 100, 1) : 0,
            ];
            $datosLinea['tipo_recomendacion'] = 'exacta_bd';
        }

        return [
            'albaran' => $source['albaran'] ?? null,
            'fecha' => $source['fecha'] ?? null,
            'pedido_codigo' => $pedidoCodigo,
            'fabricante' => $nombreFabricante,
            'peso_total' => $pesoTotal,
            'lineas_pendientes' => $candidatosSorted->toArray(), // Para IA / Debug
            'todas_las_lineas' => $candidatosSorted->toArray(), // Para lista "otros compatibles"
            'linea_propuesta' => $datosLinea,
            'estado_final_simulado' => $estadoFinalSimulado,
            'hay_coincidencias' => $datosLinea !== null,
            'bultos_albaran' => $bultosTotal,
        ];
    }

    protected function extractNumber($val)
    {
        if (is_numeric($val))
            return (float) $val;
        if (is_string($val)) {
            $v = preg_replace('/[^0-9,.]/', '', $val);
            $v = str_replace(',', '.', $v);
            return is_numeric($v) ? (float) $v : null;
        }
        return null;
    }

    protected function esLineaPermitida($linea, ?int $fabricanteId, array $diametrosEscaneados, ?string $tipoCompra = null, ?int $distribuidorId = null): bool
    {
        if (!$linea || !$linea->pedido) {
            return false;
        }

        $pedido = $linea->pedido;

        // 1. Filtrado por FABRICANTE (si se ha especificado uno)
        $lineaFabricanteId = $pedido->fabricante_id ?? null;
        if ($fabricanteId && $lineaFabricanteId && $lineaFabricanteId !== $fabricanteId) {
            return false;
        }

        // 2. Filtrado por TIPO DE COMPRA y DISTRIBUIDOR (CRÍTICO)
        if ($tipoCompra) {
            $tipoCompra = mb_strtolower($tipoCompra);
            if ($tipoCompra === 'directo') {
                // Si es directo, el pedido NO puede tener distribuidor
                if ($pedido->distribuidor_id !== null) {
                    return false;
                }
            } elseif ($tipoCompra === 'distribuidor') {
                // Si es compra a distribuidor, el pedido DEBE tener el distribuidor correcto
                if ($distribuidorId && $pedido->distribuidor_id !== $distribuidorId) {
                    return false;
                }
                // Si no tenemos distribuidorId pero el tipo es distribuidor, al menos asegurar que tenga alguno
                if (!$distribuidorId && $pedido->distribuidor_id === null) {
                    return false;
                }
            }
        }

        $diametroLinea = $linea->productoBase->diametro ?? null;

        if (!empty($diametrosEscaneados)) {
            if (!$diametroLinea) {
                return false;
            }
            $diametroLineaInt = (int) round((float) $diametroLinea);
            if (!in_array($diametroLineaInt, $diametrosEscaneados, true)) {
                return false;
            }
        }

        return true;
    }

    protected function determinarDistribuidorRecomendado(?string $tipoCompra, ?string $texto, array $distribuidores): ?string
    {
        if ($tipoCompra === 'directo') {
            return null;
        }

        $textoNormalizado = trim(Str::lower($texto ?? ''));
        if ($textoNormalizado === '') {
            return $distribuidores[0] ?? null;
        }

        foreach ($distribuidores as $nombre) {
            if (!$nombre) {
                continue;
            }
            $nombreNormalizado = Str::lower($nombre);
            if ($this->coincideDistribuidorConTexto($textoNormalizado, $nombreNormalizado)) {
                return $nombre;
            }
        }

        foreach ($distribuidores as $nombre) {
            if (!$nombre) {
                continue;
            }
            $nombreNormalizado = Str::lower($nombre);
            $palabras = preg_split('/\\s+/', $textoNormalizado);
            foreach ($palabras as $palabra) {
                if ($palabra && Str::contains($nombreNormalizado, $palabra)) {
                    return $nombre;
                }
            }
        }

        return $distribuidores[0] ?? null;
    }

    protected function coincideDistribuidorConTexto(string $textoNormalizado, string $nombreNormalizado): bool
    {
        return Str::contains($textoNormalizado, $nombreNormalizado) || Str::contains($nombreNormalizado, $textoNormalizado);
    }

    protected function obtenerNombreProveedor(?string $codigo): string
    {
        return match ($codigo) {
            'siderurgica' => 'Siderúrgica Sevillana (SISE)',
            'megasa' => 'Megasa',
            'balboa' => 'Balboa',
            default => 'Otro / No identificado',
        };
    }

    /**
     * Helper para obtener la recomendación de distribuidor por IA (usado en procesar y procesarAjax).
     * Evita duplicación de código.
     * Retorna array: ['recomendacion' => ?string, 'debug' => ?array]
     */
    protected function obtenerDistribuidorIA(array $parsed, ?string $rawText, array $distribuidores): array
    {
        if (($parsed['tipo_compra'] ?? '') !== 'distribuidor') {
            return ['recomendacion' => null, 'debug' => null];
        }

        try {
            /** @var PrioridadIAService $aiService */
            $aiService = app(PrioridadIAService::class);
            $contexto = $parsed;
            $contexto['raw_text'] = $rawText ?? '';
            return $aiService->recomendarDistribuidor($contexto, $distribuidores);
        } catch (\Throwable $e) {
            Log::warning('Fallo al recomendar distribuidor con IA: ' . $e->getMessage());
            return ['recomendacion' => null, 'debug' => ['error' => $e->getMessage()]];
        }
    }

    /**
     * Guarda el aprendizaje/retroalimentación de la IA cuando el usuario elige un pedido.
     */
    public function guardarAprendizaje(Request $request)
    {
        $request->validate([
            'ocr_log_id' => 'required|exists:entrada_import_logs,id',
            'payload_ocr' => 'required|array',
            'recomendaciones_ia' => 'required|array',
            'pedido_seleccionado_id' => 'required',
            'es_discrepancia' => 'required|boolean',
            'motivo_usuario' => 'nullable|string',
        ]);

        IAAprendizajePrioridad::create([
            'entrada_import_log_id' => $request->ocr_log_id,
            'payload_ocr' => $request->payload_ocr,
            'recomendaciones_ia' => $request->recomendaciones_ia,
            'pedido_seleccionado_id' => $request->pedido_seleccionado_id,
            'es_discrepancia' => $request->es_discrepancia,
            'motivo_usuario' => $request->motivo_usuario,
            'contexto_sistema' => [
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'timestamp' => now()->toDateTimeString(),
            ],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Activa el albarán escaneado creando la Entrada y los Productos correspondientes.
     */
    public function activar(Request $request)
    {
        $request->validate([
            'parsed' => 'required|array',
            'linea_seleccionada' => 'required|array',
            'simulacion' => 'nullable|array',
            'coladas' => 'nullable|array',
            'coladas.*.colada' => 'nullable|string|max:255',
            'coladas.*.bulto' => 'nullable|numeric|min:0',
            'coladas.*.bultos' => 'nullable|numeric|min:0',
        ]);

        $parsed = $request->input('parsed');
        $lineaData = $request->input('linea_seleccionada');
        // $simulacion = $request->input('simulacion');

        DB::beginTransaction();

        try {
            $albaranCodigo = $parsed['albaran'] ?? ('ALB-' . time());
            $pedidoProductoId = $lineaData['id'];

            // Buscar la línea real para asegurar consistencia
            $pedidoProducto = PedidoProducto::with(['pedido.fabricante', 'pedido.distribuidor', 'productoBase'])->findOrFail($pedidoProductoId);
            $pedidoId = $pedidoProducto->pedido_id;

            // Datos generales
            $pesoTotal = 0;
            $productosData = $parsed['productos'] ?? [];

            // Calcular peso total
            foreach ($productosData as $prod) {
                foreach (($prod['line_items'] ?? []) as $item) {
                    // Solo contar si no se ha desmarcado
                    if (isset($item['descargar']) && $item['descargar'] === false)
                        continue;

                    $peso = (float) ($item['peso_kg'] ?? ($item['pesoNeto'] ?? 0));
                    $pesoTotal += $peso;
                }
            }

            $debeCrearEntrada = $pesoTotal > 0;

            // Crear asignación de coladas/bultos y movimiento de entrada para el gruista (como en /pedidos)
            $productoBaseId = $pedidoProducto->producto_base_id;
            $fabricanteId = $pedidoProducto->pedido?->fabricante_id;

            $coladasPayload = (array) $request->input('coladas', []);
            if (empty($coladasPayload)) {
                foreach ($productosData as $prod) {
                    foreach (($prod['line_items'] ?? $prod['lineItems'] ?? []) as $item) {
                        if (isset($item['descargar']) && $item['descargar'] === false) {
                            continue;
                        }
                        $coladasPayload[] = [
                            'colada' => $item['colada'] ?? null,
                            'bulto' => $item['bultos'] ?? ($item['bulto'] ?? null),
                        ];
                    }
                }
            }

            $coladasPayload = collect($coladasPayload)
                ->map(function ($fila) {
                    $colada = isset($fila['colada']) ? trim((string) $fila['colada']) : null;
                    $bultoRaw = $fila['bulto'] ?? ($fila['bultos'] ?? null);
                    $bulto = is_null($bultoRaw) ? null : (float) $bultoRaw;
                    if ($colada === '') {
                        $colada = null;
                    }
                    return ['colada' => $colada, 'bulto' => $bulto];
                })
                ->filter(function ($fila) {
                    $hasColada = !is_null($fila['colada']);
                    $hasBulto = !is_null($fila['bulto']) && (float) $fila['bulto'] > 0;
                    return $hasColada || $hasBulto;
                })
                ->values()
                ->all();

            PedidoProductoColada::where('pedido_producto_id', $pedidoProducto->id)->delete();
            foreach ($coladasPayload as $fila) {
                $numeroColada = $fila['colada'] ?? null;
                $bulto = $fila['bulto'] ?? null;

                $coladaId = null;
                if (!is_null($numeroColada) && $numeroColada !== '') {
                    $coladaRegistro = Colada::firstOrCreate(
                        [
                            'numero_colada' => $numeroColada,
                            'producto_base_id' => $productoBaseId,
                        ],
                        [
                            'fabricante_id' => $fabricanteId,
                        ]
                    );
                    $coladaId = $coladaRegistro->id;

                    if ($fabricanteId && !$coladaRegistro->fabricante_id) {
                        $coladaRegistro->update(['fabricante_id' => $fabricanteId]);
                    }
                }

                PedidoProductoColada::create([
                    'pedido_producto_id' => $pedidoProducto->id,
                    'colada_id' => $coladaId,
                    'colada' => $numeroColada,
                    'bulto' => $bulto,
                    'user_id' => Auth::id(),
                ]);
            }

            $pedido = $pedidoProducto->pedido;
            $productoBase = $pedidoProducto->productoBase;
            $proveedorNombre = $pedido?->fabricante?->nombre
                ?? $pedido?->distribuidor?->nombre
                ?? 'No especificado';

            $fechaEntregaFmt = $pedidoProducto->fecha_estimada_entrega
                ? Carbon::parse($pedidoProducto->fecha_estimada_entrega)->format('d/m/Y')
                : '—';

            $partes = [];
            if ($productoBase) {
                $partes[] = sprintf(
                    'Se solicita descarga para producto %s Ø%s%s',
                    $productoBase->tipo,
                    (string) $productoBase->diametro,
                    $productoBase->tipo === 'barra' ? (' de ' . (string) $productoBase->longitud . ' m') : ''
                );
            } else {
                $partes[] = 'Se solicita descarga para albarán escaneado';
            }
            $partes[] = sprintf('Pedido %s', $pedido?->codigo ?? $pedidoId);
            $partes[] = sprintf('Proveedor: %s', $proveedorNombre);
            $partes[] = sprintf('Línea: %s', $pedidoProducto->codigo ?? $pedidoProducto->id);
            if (!is_null($pedidoProducto->cantidad)) {
                $partes[] = sprintf(
                    'Cantidad solicitada: %s kg',
                    rtrim(rtrim(number_format((float) $pedidoProducto->cantidad, 3, ',', '.'), '0'), ',')
                );
            }
            $partes[] = sprintf('Fecha prevista: %s', $fechaEntregaFmt);
            $partes[] = sprintf('Albarán: %s', $albaranCodigo);

            $descripcionMovimiento = implode(' | ', $partes);

            $ocrLogId = $parsed['ocr_log_id'] ?? null;
            $movimiento = Movimiento::query()
                ->where('tipo', 'entrada')
                ->where('estado', 'pendiente')
                ->where('pedido_producto_id', $pedidoProducto->id)
                ->first();

            if ($movimiento) {
                $movimiento->update([
                    'descripcion' => $descripcionMovimiento,
                    'ocr_log_id' => $ocrLogId,
                    'pedido_id' => $pedidoId,
                    'producto_base_id' => $productoBaseId,
                    'nave_id' => $pedidoProducto->obra_id,
                ]);
            } else {
                $movimiento = Movimiento::create([
                    'tipo' => 'entrada',
                    'estado' => 'pendiente',
                    'descripcion' => $descripcionMovimiento,
                    'fecha_solicitud' => now(),
                    'solicitado_por' => Auth::id(),
                    'pedido_id' => $pedidoId,
                    'producto_base_id' => $productoBaseId,
                    'pedido_producto_id' => $pedidoProducto->id,
                    'ocr_log_id' => $ocrLogId,
                    'prioridad' => 2,
                    'nave_id' => $pedidoProducto->obra_id,
                ]);
            }

            // Si no hay peso (p.ej. modo manual), solo creamos la solicitud de descarga (movimiento + coladas).
            // El peso real se introducirá/cerrará más adelante desde la grúa / cierre de albarán.
            if (!$debeCrearEntrada) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'entrada_id' => null,
                    'movimiento_id' => $movimiento?->id,
                    'message' => 'Solicitud creada. El peso se introducirá más adelante desde la grúa.',
                ]);
            }

            // Crear ENTRADA
            $entrada = Entrada::create([
                'albaran' => $albaranCodigo,
                'usuario_id' => Auth::id(),
                'peso_total' => $pesoTotal,
                'estado' => 'cerrado',
                'otros' => 'Escaneo Móvil Albarán ' . $albaranCodigo,
                'pedido_id' => $pedidoId,
                'pedido_producto_id' => $pedidoProductoId,
            ]);

            // Generar Códigos MP
            $ultimoCodigo = Producto::where('codigo', 'like', 'MP%')
                ->whereRaw('LENGTH(codigo) <= 12') // Evitar códigos raros largos
                ->orderByRaw('LENGTH(codigo) DESC')
                ->orderBy('codigo', 'desc')
                ->value('codigo');

            $numeroSecuencia = 1;

            if ($ultimoCodigo) {
                // Intentar extraer números al final
                if (preg_match('/MP-?(\d+)/i', $ultimoCodigo, $matches)) {
                    $numeroSecuencia = intval($matches[1]) + 1;
                }
            }

            $count = 0;

            foreach ($productosData as $prod) {
                $lineItems = $prod['line_items'] ?? [];

                foreach ($lineItems as $item) {
                    if (isset($item['descargar']) && $item['descargar'] === false)
                        continue;

                    $peso = (float) ($item['peso_kg'] ?? ($item['pesoNeto'] ?? 0));
                    $colada = $item['colada'] ?? '';
                    $paqueteMain = $item['paquete'] ?? '';
                    if (!$paqueteMain) {
                        // Generar un código de paquete
                        $paqueteMain = 'PAQ-' . date('ymd') . '-' . ($count + 1);
                    }

                    // Generar código
                    $codigoMP = 'MP' . str_pad($numeroSecuencia + $count, 6, '0', STR_PAD_LEFT);
                    $count++;

                    Producto::create([
                        'codigo' => $codigoMP,
                        'producto_base_id' => $productoBaseId,
                        'fabricante_id' => $pedidoProducto->pedido->fabricante_id,
                        'entrada_id' => $entrada->id,
                        'n_colada' => $colada,
                        'n_paquete' => $paqueteMain,
                        'peso_inicial' => $peso,
                        'peso_stock' => $peso,
                        'estado' => 'almacenado',
                        'obra_id' => $pedidoProducto->obra_id ?? 0,
                        'ubicacion_id' => null, // Pendiente de asignación
                        'maquina_id' => null,
                        'otros' => 'Scan Móvil',
                    ]);
                }
            }

            // Actualizar OCR Log
            $ocrLogId = $parsed['ocr_log_id'] ?? $request->input('ocr_log_id');

            if (!empty($ocrLogId)) {
                $log = EntradaImportLog::find($ocrLogId);
                if ($log) {
                    $log->update([
                        'entrada_id' => $entrada->id,
                        'status' => 'applied',
                        'reviewed_at' => now(),
                        'applied_payload' => $parsed
                    ]);

                    // Copiar PDF
                    try {
                        if ($log->file_path && Storage::disk('private')->exists($log->file_path)) {
                            $ext = pathinfo($log->file_path, PATHINFO_EXTENSION) ?: 'pdf';
                            $destName = 'albaran_' . $entrada->id . '_' . time() . '.' . $ext;
                            $destPath = 'albaranes_entrada/' . $destName;

                            Storage::disk('private')->copy($log->file_path, $destPath);
                            $entrada->pdf_albaran = $destName;
                            $entrada->save();
                            Log::info("PDF albarán copiado exitosamente: {$destPath}");
                        } else {
                            Log::warning("No se encontró archivo original para copiar: {$log->file_path}");
                        }
                    } catch (\Throwable $e2) {
                        Log::warning("No se pudo copiar archivo albarán: " . $e2->getMessage());
                    }
                } else {
                    Log::warning("No se encontró EntradaImportLog con ID: {$ocrLogId}");
                }
            } else {
                Log::warning("Se activó albarán sin ocr_log_id. No se pudo adjuntar PDF.");
            }

            // IMPORTANTE:
            // No cambiamos el estado de la línea aquí (pendiente/parcial/completado).
            // Este endpoint se usa para solicitar la descarga (movimiento + coladas/bultos).
            // El estado de recepción de la línea se recalcula en el flujo de cierre (p.ej. EntradaController@cerrarAlbaranPorMovimiento).
            Log::info("Albaran scan activado. Entrada: {$entrada->id}. PedidoProducto ID: {$pedidoProducto->id}. Estado línea sin cambios: {$pedidoProducto->estado}");

            // Registrar asociación dirección-distribuidor para aprendizaje de IA
            $this->registrarDireccionDistribuidor($parsed, $pedidoProducto);

            DB::commit();

            return response()->json([
                'success' => true,
                'entrada_id' => $entrada->id,
                'message' => 'Albarán activado correctamente'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error activando albaran:', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al activar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registra o actualiza la asociación dirección-distribuidor para mejorar recomendaciones de IA.
     * Si la dirección ya existe con otro distribuidor, borra el anterior y crea uno nuevo.
     */
    protected function registrarDireccionDistribuidor(array $parsed, PedidoProducto $pedidoProducto): void
    {
        try {
            // Extraer proveedorTexto del payload
            $proveedorTexto = $parsed['proveedorTexto'] ?? $parsed['proveedor_texto'] ?? null;

            if (!$proveedorTexto) {
                Log::info('No hay proveedorTexto en parsed payload, no se registra dirección-distribuidor');
                return;
            }

            // Normalizar a minúsculas para comparación
            $direccionMatch = mb_strtolower(trim($proveedorTexto));

            if (strlen($direccionMatch) < 3) {
                Log::info('proveedorTexto demasiado corto para registrar como dirección');
                return;
            }

            // Determinar el distribuidor seleccionado (del pedido)
            $distribuidorId = $pedidoProducto->pedido->distribuidor_id ?? null;

            if (!$distribuidorId) {
                Log::info('Pedido sin distribuidor_id, no se registra dirección-distribuidor');
                return;
            }

            // Buscar si ya existe esta dirección
            $existente = DistribuidorDireccion::where('direccion_match', $direccionMatch)->first();

            if ($existente) {
                if ($existente->distribuidor_id !== $distribuidorId) {
                    // Caso 1: Existe pero con diferente distribuidor -> borrar y crear nuevo
                    Log::info("Dirección '{$direccionMatch}' cambió de distribuidor {$existente->distribuidor_id} → {$distribuidorId}. Actualizando registro.");
                    $existente->delete();

                    DistribuidorDireccion::create([
                        'distribuidor_id' => $distribuidorId,
                        'direccion_match' => $direccionMatch,
                    ]);
                } else {
                    // Ya existe con el mismo distribuidor, actualizar timestamp (touch)
                    $existente->touch();
                    Log::info("Dirección '{$direccionMatch}' ya registrada con distribuidor {$distribuidorId}");
                }
            } else {
                // Caso 2: No existe -> crear nuevo
                DistribuidorDireccion::create([
                    'distribuidor_id' => $distribuidorId,
                    'direccion_match' => $direccionMatch,
                ]);
                Log::info("Nueva dirección '{$direccionMatch}' registrada con distribuidor {$distribuidorId}");
            }

        } catch (\Throwable $e) {
            Log::error('Error registrando dirección-distribuidor: ' . $e->getMessage());
        }
    }
}
