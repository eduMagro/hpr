<?php

namespace App\Http\Controllers;

use App\Models\Distribuidor;
use App\Models\PedidoProducto;
use App\Services\AlbaranOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OpenAIController extends Controller
{
    public function index()
    {
        $distribuidores = Distribuidor::query()
            ->pluck('nombre')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return view('openai.index', [
            'distribuidores' => $distribuidores,
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

        $resultados = [];

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                try {
                    $log = $service->parseAndLog($imagen, auth()->id(), $proveedor);
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
                    $parsed['distribuidor_recomendado'] = $this->determinarDistribuidorRecomendado(
                        $parsed['tipo_compra'],
                        $parsed['proveedor_texto'] ?? null,
                        $distribuidores
                    );

                    $resultados[] = [
                        'nombre_archivo' => $imagen->getClientOriginalName(),
                        'preview' => $previewData,
                        'parsed' => $parsed,
                        'raw' => $log->raw_text,
                        'simulacion' => $simulacion,
                        'status_messages' => $statusMessages,
                        'ai_meta' => $aiMeta,
                        'error' => null,
                    ];

                } catch (\Exception $e) {
                    Log::error('Error procesando imagen con OpenAI: ' . $e->getMessage());
                    $resultados[] = [
                        'nombre_archivo' => $imagen->getClientOriginalName(),
                        'preview' => null,
                        'parsed' => null,
                        'raw' => null,
                        'error' => 'Error al procesar: ' . $e->getMessage(),
                    ];
                }
            }
        }

        return view('openai.index', [
            'resultados' => $resultados,
            'proveedor' => $proveedor,
            'distribuidores' => $distribuidores,
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
                    $log = $service->parseAndLog($imagen, auth()->id(), $proveedor);
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
                    $parsed['distribuidor_recomendado'] = $this->determinarDistribuidorRecomendado(
                        $parsed['tipo_compra'],
                        $parsed['proveedor_texto'] ?? null,
                        $distribuidores
                    );

                    $resultados[] = [
                        'nombre_archivo' => $imagen->getClientOriginalName(),
                        'preview' => $previewData,
                        'parsed' => $parsed,
                        'raw' => $log->raw_text,
                        'simulacion' => $simulacion,
                        'status_messages' => $statusMessages,
                        'ai_meta' => $aiMeta,
                        'error' => null,
                    ];

                } catch (\Exception $e) {
                    Log::error('Error procesando imagen con OpenAI: ' . $e->getMessage());
                    $resultados[] = [
                        'nombre_archivo' => $imagen->getClientOriginalName(),
                        'preview' => null,
                        'parsed' => null,
                        'raw' => null,
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
        $normalized = preg_replace('/\s+/', '', mb_strtolower($codigo));
        $diametros = collect($request->input('diametros', []))
            ->filter(fn($v) => $v !== null && $v !== '')
            ->map(fn($v) => (float) $v)
            ->unique()
            ->values()
            ->all();

        if ($normalized === '') {
            return response()->json([
                'exists' => false,
                'reason' => 'empty',
                'searched' => $codigo,
            ]);
        }

        // Normaliza código de pedido padre en BD eliminando espacios y pasando a minúsculas.
        $pedidoExpr = "LOWER(REPLACE(codigo, ' ', ''))";

        $baseWith = ['pedido.fabricante', 'pedido.distribuidor', 'productoBase', 'obra'];

        $exactQuery = PedidoProducto::query()
            ->with($baseWith)
            ->whereHas('pedido', fn($q) => $q->whereRaw("{$pedidoExpr} = ?", [$normalized]));
        $exactCount = (clone $exactQuery)->count();
        $lineas = (clone $exactQuery)->orderByDesc('created_at')->limit(50)->get();

        $matchType = $lineas->isNotEmpty() ? 'exact' : 'contains';
        $containsCount = null;

        if ($lineas->isEmpty()) {
            $containsQuery = PedidoProducto::query()
                ->with($baseWith)
                ->whereHas('pedido', fn($q) => $q->whereRaw("{$pedidoExpr} LIKE ?", ['%' . $normalized . '%']));
            $containsCount = (clone $containsQuery)->count();
            $lineas = (clone $containsQuery)->orderByDesc('created_at')->limit(50)->get();
        }

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
            ->sortBy(function (PedidoProducto $linea) use ($diametros, $estadoRank) {
                $rank = $estadoRank($linea->estado);
                $diam = (float) ($linea->productoBase?->diametro ?? 0);
                $diamMatch = (!empty($diametros) && $diam > 0 && in_array($diam, $diametros, true)) ? 0 : 1;
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
            'diametros' => $diametros,
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
        // Docupipe puede devolver el payload dentro de "data"; la UI lo normaliza, pero aquí necesitamos soportarlo.
        $source = (isset($parsed['data']) && is_array($parsed['data'])) ? $parsed['data'] : $parsed;

        $productos = $source['productos'] ?? ($source['products'] ?? []);
        $proveedor = $parsed['proveedor'] ?? null;
        // En algunos OCR el código viene en "pedido_cliente"
        $pedidoCodigo = $source['pedido_cliente'] ?? ($source['pedido_codigo'] ?? null);

        $normalizeCode = static function (?string $value): string {
            $value = (string) ($value ?? '');
            $value = preg_replace('/\s+/', '', $value);
            return mb_strtolower($value);
        };

        $normalizedPedidoCodigo = $normalizeCode($pedidoCodigo);

        // Recopilar todos los line_items de todos los productos
        $allLineItems = [];
        foreach ((array) $productos as $producto) {
            if (!is_array($producto)) {
                continue;
            }
            $lineItems = $producto['line_items'] ?? [];
            foreach ((array) $lineItems as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $allLineItems[] = array_merge($item, [
                    'producto_descripcion' => $producto['descripcion'] ?? null,
                    'producto_diametro' => $producto['diametro'] ?? null,
                    'producto_calidad' => $producto['calidad'] ?? null,
                ]);
            }
        }

        $extractNumber = static function ($value): ?float {
            if ($value === null) {
                return null;
            }
            if (is_int($value) || is_float($value)) {
                return (float) $value;
            }
            $text = trim((string) $value);
            if ($text === '') {
                return null;
            }
            if (is_numeric($text)) {
                return (float) $text;
            }
            if (preg_match('/(\\d+(?:[\\.,]\\d+)?)/', $text, $m)) {
                $num = str_replace(',', '.', $m[1]);
                return is_numeric($num) ? (float) $num : null;
            }
            return null;
        };

        $extractDiameterFromText = static function ($value) use ($extractNumber): ?float {
            if ($value === null) {
                return null;
            }
            $text = trim((string) $value);
            if ($text === '') {
                return null;
            }
            $lower = mb_strtolower($text);
            // Heurística: solo intentar extraer diámetros si el texto sugiere Ø/mm/diámetro
            if (!str_contains($lower, 'ø') && !str_contains($lower, 'mm') && !str_contains($lower, 'diam')) {
                return null;
            }
            return $extractNumber($text);
        };

        // Extraer diámetros escaneados con tolerancia a diferentes formatos (ej: "Ø16", "16mm", 16).
        $diametrosEscaneados = collect()
            ->merge(collect($productos)->pluck('diametro'))
            ->merge(collect($allLineItems)->pluck('producto_diametro'))
            ->merge([data_get($source, 'producto.diametro')])
            ->merge(collect($productos)->pluck('descripcion')->map(fn($t) => $extractDiameterFromText($t)))
            ->merge(collect($allLineItems)->pluck('producto_descripcion')->map(fn($t) => $extractDiameterFromText($t)))
            ->map(fn($d) => $extractNumber($d))
            ->filter(fn($d) => $d !== null)
            ->map(fn($d) => (int) round((float) $d))
            ->filter(fn($d) => $d > 0)
            ->unique()
            ->values()
            ->toArray();
        $lineItemsWeight = collect($allLineItems)->sum('peso_kg');
        $pesoTotal = $lineItemsWeight > 0 ? $lineItemsWeight : (float) ($source['peso_total'] ?? 0);

        // Buscar FABRICANTE según proveedor (todos los pedidos tienen fabricante)
        $fabricanteId = null;
        $fabricanteNombre = 'Desconocido';

        if ($proveedor === 'siderurgica') {
            $fabricante = \App\Models\Fabricante::where('nombre', 'LIKE', '%Siderurgica%')
                ->orWhere('nombre', 'LIKE', '%SISE%')
                ->first();
            $fabricanteId = $fabricante?->id;
            $fabricanteNombre = $fabricante?->nombre ?? 'Siderúrgica Sevillana';
        } elseif ($proveedor === 'megasa') {
            $fabricante = \App\Models\Fabricante::where('nombre', 'LIKE', '%Megasa%')->first();
            $fabricanteId = $fabricante?->id;
            $fabricanteNombre = $fabricante?->nombre ?? 'Megasa';
        } elseif ($proveedor === 'balboa') {
            $fabricante = \App\Models\Fabricante::where('nombre', 'LIKE', '%Balboa%')->first();
            $fabricanteId = $fabricante?->id;
            $fabricanteNombre = $fabricante?->nombre ?? 'Balboa';
        }

        // Buscar líneas de pedidos de COMPRA pendientes (filtrar solo por FABRICANTE)
        $lineasPendientes = \App\Models\PedidoProducto::query()
            ->with(['pedido.fabricante', 'productoBase', 'obra'])
            ->whereNotIn('estado', ['completado', 'cancelado', 'facturado'])
            ->get()
            ->filter(fn($linea) => $this->esLineaPermitida($linea, $fabricanteId, $diametrosEscaneados));

        // Preparar información y scoring de líneas pendientes
        $hoy = now();

        // Encontrar el pedido más antiguo para calcular puntuación por regla de 3
        $pedidoMasAntiguo = $lineasPendientes->min(function ($linea) {
            return $linea->pedido->created_at;
        });
        $diasMaximos = $pedidoMasAntiguo ? $pedidoMasAntiguo->diffInDays($hoy) : 0;
        $puntajeAntiguedadMaximo = 10; // Puntos máximos por antigüedad

        $lineasConScoring = $lineasPendientes->map(function ($linea) use ($diametrosEscaneados, $pesoTotal, $pedidoCodigo, $normalizedPedidoCodigo, $normalizeCode, $fabricanteId, $hoy, $diasMaximos, $puntajeAntiguedadMaximo) {
            $score = 0;
            $razones = [];
            $incompatibilidades = [];

            if (!$linea->obra && !$linea->obra_manual) {
                \Log::info('Pedido pendiente sin obra detectado', [
                    'linea_id' => $linea->id,
                    'codigo' => $linea->codigo,
                    'pedido_codigo' => $linea->pedido?->codigo,
                    'pedido_id' => $linea->pedido_id,
                ]);
            }

            // SCORING 0: Fabricante (El más importante)
            if ($fabricanteId) {
                if ($linea->pedido->fabricante_id == $fabricanteId) {
                    $score += 200; // Impacto masivo
                    $razones[] = "✓ Fabricante coincide con el seleccionado";
                } elseif (is_null($linea->pedido->fabricante_id)) {
                    $score += 50; // Prioridad media (mejor que distinto)
                    $razones[] = "⚠ Pedido sin fabricante asignado (prioridad media)";
                } else {
                    $score -= 50; // Penalización
                    $incompatibilidades[] = "⚠ Fabricante distinto al seleccionado (" . ($linea->pedido->fabricante->nombre ?? 'Desconocido') . ")";
                }
            }

            // Obtener diámetro del producto base
            $diametroLinea = $linea->productoBase->diametro ?? null;
            $diametroLineaInt = $diametroLinea !== null ? (int) round((float) $diametroLinea) : null;

            // SCORING 1: Coincidencia de diámetro (crítico)
            if ($diametroLineaInt && in_array($diametroLineaInt, $diametrosEscaneados, true)) {
                $score += 50;
                $razones[] = "✓ Diámetro Ø{$diametroLineaInt} coincide";
            } elseif (!empty($diametrosEscaneados)) {
                $incompatibilidades[] = "✗ Diámetro Ø{$diametroLineaInt} no coincide con escaneado: Ø" . implode(', Ø', $diametrosEscaneados);
            }

            // SCORING 2: Coincidencia de código de pedido
            $lineaCodigo = (string) ($linea->pedido->codigo ?? '');
            $normalizedLineaCodigo = $normalizeCode($lineaCodigo);
            if ($normalizedPedidoCodigo !== '' && $normalizedLineaCodigo === $normalizedPedidoCodigo) {
                $score += 30;
                $razones[] = "✓ Código de pedido coincide exactamente";
            } elseif ($normalizedPedidoCodigo !== '' && str_contains($normalizedLineaCodigo, $normalizedPedidoCodigo)) {
                $score += 15;
                $razones[] = "≈ Código de pedido similar";
            }

            $hoy = now();

            // SCORING 3: Cantidad pendiente suficiente
            $cantidadPendienteKg = ($linea->cantidad ?? 0) - ($linea->cantidad_recepcionada ?? 0);
            if ($pesoTotal <= $cantidadPendienteKg) {
                $score += 10;
                $razones[] = "✓ Cantidad pendiente suficiente ({$cantidadPendienteKg} kg)";
            } else {
                $sobra = $pesoTotal - $cantidadPendienteKg;
                $incompatibilidades[] = "⚠ Cantidad importada supera la pendiente en {$sobra} kg ({$cantidadPendienteKg} kg esperados < {$pesoTotal} kg)";
            }

            // SCORING 4: Antigüedad del pedido (regla de 3 basada en el más antiguo)
            $diasDesdeCreacion = $linea->pedido->created_at->diffInDays($hoy);
            if ($diasMaximos > 0) {
                // Regla de 3: el pedido más antiguo obtiene el máximo puntaje
                $puntajeAntiguedad = ($diasDesdeCreacion / $diasMaximos) * $puntajeAntiguedadMaximo;
                $score += $puntajeAntiguedad;

                if ($diasDesdeCreacion == $diasMaximos) {
                    $razones[] = "✓ Pedido MÁS ANTIGUO ({$diasDesdeCreacion} días) - máxima prioridad";
                } elseif ($puntajeAntiguedad >= 7) {
                    $razones[] = "✓ Pedido muy antiguo ({$diasDesdeCreacion} días)";
                } elseif ($puntajeAntiguedad >= 5) {
                    $razones[] = "✓ Pedido antiguo ({$diasDesdeCreacion} días)";
                } elseif ($puntajeAntiguedad >= 3) {
                    $razones[] = "✓ Pedido con antigüedad media ({$diasDesdeCreacion} días)";
                } else {
                    $razones[] = "⚠ Pedido reciente ({$diasDesdeCreacion} días) - prioridad baja";
                }
            } else {
                // Si todos los pedidos tienen la misma fecha, no hay diferencia de antigüedad
                $razones[] = "≈ Mismo día que otros pedidos";
            }


            // Obtener fabricante (TODOS los pedidos tienen fabricante)
            $fabricante = $linea->pedido->fabricante->nombre ?? '(sin fabricante)';

            // Construir descripción del producto
            $productoDescripcion = $linea->productoBase->nombre ?? null;
            if (!$productoDescripcion && $diametroLineaInt) {
                $productoDescripcion = "Ø{$diametroLineaInt}mm";
            } elseif (!$productoDescripcion) {
                $productoDescripcion = "ProductoBase #{$linea->producto_base_id} (no encontrado)";
            }

            return [
                'id' => $linea->id,
                'pedido_id' => $linea->pedido_id,
                'pedido_codigo' => $linea->pedido->codigo ?? '(sin código)',
                'fabricante' => $fabricante,
                'obra' => $linea->obra->obra ?? $linea->obra_manual ?? '(sin obra)',
                'producto' => $productoDescripcion,
                'diametro' => $diametroLineaInt,
                'cantidad' => $linea->cantidad ?? 0,
                'cantidad_recepcionada' => $linea->cantidad_recepcionada ?? 0,
                'cantidad_pendiente' => $cantidadPendienteKg,
                'estado' => $linea->estado,
                'fecha_creacion' => $linea->pedido->created_at->format('d/m/Y'),
                'score' => $score,
                'razones' => $razones,
                'incompatibilidades' => $incompatibilidades,
                'es_viable' => count($incompatibilidades) === 0,
            ];
        })->sortByDesc('score')->values()->toArray();

        // Obtener TODAS las líneas pendientes/parciales (sin filtro de fabricante)
        // Para que el usuario pueda elegir manualmente si lo desea
        $todasLasLineasQuery = \App\Models\PedidoProducto::query()
            ->with(['pedido.fabricante', 'productoBase', 'obra'])
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->whereHas('pedido', function ($q) {
                $q->whereNotIn('estado', ['completado', 'cancelado', 'facturado']);
            })
            ->get()
            ->filter(fn($linea) => $this->esLineaPermitida($linea, $fabricanteId, $diametrosEscaneados));

        // Encontrar el pedido más antiguo de TODAS las líneas para calcular puntuación
        $pedidoMasAntiguoTodas = $todasLasLineasQuery->min(function ($linea) {
            return $linea->pedido->created_at;
        });
        $diasMaximosTodas = $pedidoMasAntiguoTodas ? $pedidoMasAntiguoTodas->diffInDays(now()) : 0;

        $todasLasLineas = $todasLasLineasQuery->map(function ($linea) use ($diametrosEscaneados, $pesoTotal, $pedidoCodigo, $normalizedPedidoCodigo, $normalizeCode, $fabricanteId, $diasMaximosTodas, $puntajeAntiguedadMaximo) {
            // Calcular scoring para cada línea (mismo algoritmo que arriba)
            $score = 0;
            $cantidadPendiente = ($linea->cantidad ?? 0) - ($linea->cantidad_recepcionada ?? 0);
            $diametroLinea = $linea->productoBase->diametro ?? null;
            $diametroLineaInt = $diametroLinea !== null ? (int) round((float) $diametroLinea) : null;
            $fabricante = $linea->pedido->fabricante->nombre ?? '(sin fabricante)';

            // SCORING 0: Fabricante
            if ($fabricanteId) {
                if ($linea->pedido->fabricante_id == $fabricanteId) {
                    $score += 200;
                } elseif (is_null($linea->pedido->fabricante_id)) {
                    $score += 50;
                } else {
                    $score -= 50;
                }
            }

            // SCORING 1: Diámetro
            if ($diametroLineaInt && in_array($diametroLineaInt, $diametrosEscaneados, true)) {
                $score += 50;
            }

            // SCORING 2: Código de pedido
            $lineaCodigo = (string) ($linea->pedido->codigo ?? '');
            $normalizedLineaCodigo = $normalizeCode($lineaCodigo);
            if ($normalizedPedidoCodigo !== '' && $normalizedLineaCodigo === $normalizedPedidoCodigo) {
                $score += 30;
            } elseif ($normalizedPedidoCodigo !== '' && str_contains($normalizedLineaCodigo, $normalizedPedidoCodigo)) {
                $score += 15;
            }

            // SCORING 3: Cantidad pendiente
            if ($cantidadPendiente >= $pesoTotal) {
                $score += 10;
            }

            // SCORING 4: Antigüedad (regla de 3 basada en el más antiguo)
            $diasDesdeCreacion = $linea->pedido->created_at->diffInDays(now());
            if ($diasMaximosTodas > 0) {
                $puntajeAntiguedad = ($diasDesdeCreacion / $diasMaximosTodas) * $puntajeAntiguedadMaximo;
                $score += $puntajeAntiguedad;
            }

            $productoDescripcion = $linea->productoBase->nombre ?? null;
            if (!$productoDescripcion && $diametroLineaInt) {
                $productoDescripcion = "Ø{$diametroLineaInt}mm";
            } elseif (!$productoDescripcion) {
                $productoDescripcion = "ProductoBase #{$linea->producto_base_id} (no encontrado)";
            }

            // Indicar si coincide con diámetros escaneados
            $coincideDiametro = $diametroLineaInt && in_array($diametroLineaInt, $diametrosEscaneados, true);

            return [
                'id' => $linea->id,
                'pedido_id' => $linea->pedido_id,
                'pedido_codigo' => $linea->pedido->codigo ?? '(sin código)',
                'fabricante' => $fabricante,
                'obra' => $linea->obra->obra ?? $linea->obra_manual ?? '(sin obra)',
                'producto' => $productoDescripcion,
                'diametro' => $diametroLineaInt,
                'cantidad' => $linea->cantidad ?? 0,
                'cantidad_recepcionada' => $linea->cantidad_recepcionada ?? 0,
                'cantidad_pendiente' => $cantidadPendiente,
                'estado' => $linea->estado,
                'fecha_creacion' => $linea->pedido->created_at->format('d/m/Y'),
                'score' => $score,
                'coincide_diametro' => $coincideDiametro,
            ];
        })
            ->sortByDesc('score')  // Ordenar por score de mayor a menor
            ->values()
            ->toArray();

        // Línea propuesta (siempre seleccionar una): priorizar coincidencia de código (si existe alguna línea abierta)
        $lineaPropuesta = $lineasConScoring[0] ?? null;
        $tipoRecomendacion = null;

        if ($lineaPropuesta) {
            if ($normalizedPedidoCodigo !== '') {
                $coincidentes = collect($lineasConScoring)
                    ->filter(function ($l) use ($normalizeCode, $normalizedPedidoCodigo) {
                        $codigoLinea = $normalizeCode($l['pedido_codigo'] ?? '');
                        return $codigoLinea !== '' && str_contains($codigoLinea, $normalizedPedidoCodigo);
                    })
                    ->values()
                    ->all();

                if (!empty($coincidentes)) {
                    $lineaPropuesta = $coincidentes[0]; // ya vienen ordenadas por score
                }
            }

            $propuestaNorm = $normalizeCode($lineaPropuesta['pedido_codigo'] ?? '');
            if ($normalizedPedidoCodigo !== '' && $propuestaNorm === $normalizedPedidoCodigo) {
                $tipoRecomendacion = 'exacta';
            } elseif ($normalizedPedidoCodigo !== '' && str_contains($propuestaNorm, $normalizedPedidoCodigo)) {
                $tipoRecomendacion = 'parcial';
            } else {
                $tipoRecomendacion = 'por_score';
            }
        } else {
            // Si no hay coincidencias con código, buscar en TODAS las líneas por score
            if (!empty($todasLasLineas)) {
                $lineaPropuesta = $todasLasLineas[0]; // Ya está ordenado por score
                $tipoRecomendacion = 'por_score';
                // Agregar campos faltantes para compatibilidad
                $lineaPropuesta['razones'] = [
                    "⚠ No se encontró pedido con código '{$pedidoCodigo}'",
                ];
                $lineaPropuesta['incompatibilidades'] = [
                    "⚠ El código de pedido del albarán no coincide con ningún pedido en BD"
                ];
                $lineaPropuesta['es_viable'] = true;
            }
        }

        // Agregar tipo de recomendación a la línea propuesta
        if ($lineaPropuesta) {
            $lineaPropuesta['tipo_recomendacion'] = $tipoRecomendacion;
        }

        // Preparar productos escaneados para mostrar
        $productosEscaneados = collect($productos)->map(function ($prod, $index) {
            return [
                'numero' => $index + 1,
                'diametro' => $prod['diametro'] ?? '—',
                'peso_kg' => $prod['peso_kg'] ?? null,
                'descripcion' => $prod['descripcion'] ?? '—',
            ];
        })->toArray();

        // Estado final simulado de la línea
        $estadoFinalSimulado = null;
        if ($lineaPropuesta && $lineaPropuesta['es_viable']) {
            $nuevaCantidadRecepcionada = $lineaPropuesta['cantidad_recepcionada'] + $pesoTotal;
            $nuevoEstado = $nuevaCantidadRecepcionada >= $lineaPropuesta['cantidad'] ? 'completado' : 'parcial';

            $estadoFinalSimulado = [
                'cantidad_recepcionada_nueva' => $nuevaCantidadRecepcionada,
                'cantidad_total' => $lineaPropuesta['cantidad'],
                'estado_nuevo' => $nuevoEstado,
                'progreso' => round(($nuevaCantidadRecepcionada / $lineaPropuesta['cantidad']) * 100, 1),
            ];
        }

        $bultosSimulados = collect($allLineItems)->map(function ($item, $index) {
            return [
                'numero' => $index + 1,
                'colada' => $item['colada'] ?? '—',
                'bultos' => (int) ($item['bultos'] ?? 1),
                'peso_kg' => $item['peso_kg'] ?? null,
                'producto_descripcion' => $item['producto_descripcion'] ?? '—',
                'producto_diametro' => $item['producto_diametro'] ?? '—',
                'producto_calidad' => $item['producto_calidad'] ?? '—',
                'estado_simulado' => 'Se crearía',
            ];
        })->values()->toArray();
        $bultosTotal = collect($bultosSimulados)->sum('bultos');

        return [
            'albaran' => $parsed['albaran'] ?? null,
            'fecha' => $parsed['fecha'] ?? null,
            'pedido_codigo' => $pedidoCodigo,
            'fabricante' => $fabricanteNombre,
            'diametros_escaneados' => $diametrosEscaneados,
            'peso_total' => $pesoTotal,
            'productos_escaneados' => $productosEscaneados,
            'lineas_pendientes' => $lineasConScoring,
            'todas_las_lineas' => $todasLasLineas,
            'linea_propuesta' => $lineaPropuesta,
            'estado_final_simulado' => $estadoFinalSimulado,
            'hay_coincidencias' => $lineaPropuesta !== null, // True si hay línea propuesta (por código o por score)
            'bultos_albaran' => $bultosTotal,
            'bultos_simulados' => $bultosSimulados,
        ];
    }

    protected function esLineaPermitida($linea, ?int $fabricanteId, array $diametrosEscaneados): bool
    {
        $lineaFabricanteId = $linea->pedido->fabricante_id ?? null;
        if ($fabricanteId && $lineaFabricanteId && $lineaFabricanteId !== $fabricanteId) {
            return false;
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
}
