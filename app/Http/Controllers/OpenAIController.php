<?php

namespace App\Http\Controllers;

use App\Services\AlbaranOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OpenAIController extends Controller
{
    public function index()
    {
        return view('openai.index');
    }

    public function procesar(Request $request, AlbaranOcrService $service)
    {
        $request->validate([
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
            'proveedor' => 'nullable|string|in:siderurgica,megasa,balboa,otro',
        ]);

        $proveedor = $request->input('proveedor');

        $resultados = [];

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                try {
                    $log = $service->parseAndLog($imagen, auth()->id(), $proveedor);
                    $parsed = $log->parsed_payload ?? [];

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

                    $resultados[] = [
                        'nombre_archivo' => $imagen->getClientOriginalName(),
                        'preview' => $previewData,
                        'parsed' => $parsed,
                        'raw' => $log->raw_text,
                        'simulacion' => $simulacion,
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
            'proveedor' => $proveedor
        ]);
    }

    /**
     * Genera una simulación sugiriendo qué línea de pedido de COMPRA activar
     */
    protected function generarSimulacion(array $parsed): array
    {
        $productos = $parsed['productos'] ?? [];
        $proveedor = $parsed['proveedor'] ?? null;
        $pedidoCodigo = $parsed['pedido_codigo'] ?? null;

        // Recopilar todos los line_items de todos los productos
        $allLineItems = [];
        foreach ($productos as $producto) {
            $lineItems = $producto['line_items'] ?? [];
            foreach ($lineItems as $item) {
                $allLineItems[] = array_merge($item, [
                    'producto_descripcion' => $producto['descripcion'] ?? null,
                    'producto_diametro' => $producto['diametro'] ?? null,
                    'producto_calidad' => $producto['calidad'] ?? null,
                ]);
            }
        }

        // Extraer diámetros de los productos escaneados
        $diametrosEscaneados = collect($productos)->pluck('diametro')->filter()->unique()->values()->toArray();
        $bultosTotal = $parsed['bultos_total'] ?? collect($allLineItems)->sum('bultos') ?: 0;
        $pesoTotal = collect($allLineItems)->sum('peso_kg') ?: 0;

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
            ->whereHas('pedido', function($q) use ($fabricanteId, $pedidoCodigo) {
                // Filtrar por fabricante (OBLIGATORIO - todos los pedidos tienen fabricante)
                if ($fabricanteId) {
                    $q->where('fabricante_id', $fabricanteId);
                }

                // Si hay código de pedido, intentar coincidir
                if ($pedidoCodigo) {
                    $q->where('codigo', 'LIKE', "%{$pedidoCodigo}%");
                }
            })
            ->whereNotIn('estado', ['completado', 'cancelado', 'facturado'])
            ->get();

        // Preparar información y scoring de líneas pendientes
        $lineasConScoring = $lineasPendientes->map(function($linea) use ($diametrosEscaneados, $pesoTotal, $pedidoCodigo) {
            $score = 0;
            $razones = [];
            $incompatibilidades = [];

            // Obtener diámetro del producto base
            $diametroLinea = $linea->productoBase->diametro ?? null;

            // SCORING 1: Coincidencia de diámetro (crítico)
            if ($diametroLinea && in_array($diametroLinea, $diametrosEscaneados)) {
                $score += 50;
                $razones[] = "✓ Diámetro Ø{$diametroLinea} coincide";
            } else {
                $incompatibilidades[] = "✗ Diámetro Ø{$diametroLinea} no coincide con escaneado: Ø" . implode(', Ø', $diametrosEscaneados);
            }

            // SCORING 2: Coincidencia de código de pedido
            if ($pedidoCodigo && $linea->pedido->codigo === $pedidoCodigo) {
                $score += 30;
                $razones[] = "✓ Código de pedido coincide exactamente";
            } elseif ($pedidoCodigo && stripos($linea->pedido->codigo, $pedidoCodigo) !== false) {
                $score += 15;
                $razones[] = "≈ Código de pedido similar";
            }

            // SCORING 3: Cantidad pendiente suficiente
            $cantidadPendienteKg = ($linea->cantidad ?? 0) - ($linea->cantidad_recepcionada ?? 0);
            if ($cantidadPendienteKg >= $pesoTotal) {
                $score += 10;
                $razones[] = "✓ Cantidad pendiente suficiente ({$cantidadPendienteKg} kg)";
            } else {
                $incompatibilidades[] = "⚠ Cantidad pendiente insuficiente ({$cantidadPendienteKg} kg < {$pesoTotal} kg)";
            }

            // SCORING 4: Antigüedad del pedido (priorizar más antiguos)
            $diasDesdeCreacion = $linea->pedido->created_at->diffInDays(now());
            if ($diasDesdeCreacion > 7) {
                $score += 5;
                $razones[] = "⏰ Pedido antiguo ({$diasDesdeCreacion} días)";
            }

            // Obtener fabricante (TODOS los pedidos tienen fabricante)
            $fabricante = $linea->pedido->fabricante->nombre ?? '(sin fabricante)';

            // Construir descripción del producto
            $productoDescripcion = $linea->productoBase->nombre ?? null;
            if (!$productoDescripcion && $diametroLinea) {
                $productoDescripcion = "Ø{$diametroLinea}mm";
            } elseif (!$productoDescripcion) {
                $productoDescripcion = "ProductoBase #{$linea->producto_base_id} (no encontrado)";
            }

            return [
                'id' => $linea->id,
                'pedido_codigo' => $linea->pedido->codigo ?? '(sin código)',
                'fabricante' => $fabricante,
                'obra' => $linea->obra->nombre ?? $linea->obra_manual ?? '(sin obra)',
                'producto' => $productoDescripcion,
                'diametro' => $diametroLinea,
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

        // Línea propuesta (la con mayor score)
        $lineaPropuesta = $lineasConScoring[0] ?? null;

        // Obtener TODAS las líneas pendientes/parciales (sin filtro de fabricante)
        // Para que el usuario pueda elegir manualmente si lo desea
        $todasLasLineas = \App\Models\PedidoProducto::query()
            ->with(['pedido.fabricante', 'productoBase', 'obra'])
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->whereHas('pedido', function($q) {
                // Solo pedidos que NO estén completados/cancelados/facturados
                $q->whereNotIn('estado', ['completado', 'cancelado', 'facturado']);
            })
            ->get()
            ->map(function($linea) use ($diametrosEscaneados, $pesoTotal, $pedidoCodigo) {
                // Calcular scoring para cada línea (mismo algoritmo que arriba)
                $score = 0;
                $cantidadPendiente = ($linea->cantidad ?? 0) - ($linea->cantidad_recepcionada ?? 0);
                $diametroLinea = $linea->productoBase->diametro ?? null;
                $fabricante = $linea->pedido->fabricante->nombre ?? '(sin fabricante)';

                // SCORING 1: Diámetro
                if ($diametroLinea && in_array($diametroLinea, $diametrosEscaneados)) {
                    $score += 50;
                }

                // SCORING 2: Código de pedido
                if ($pedidoCodigo && $linea->pedido->codigo === $pedidoCodigo) {
                    $score += 30;
                } elseif ($pedidoCodigo && stripos($linea->pedido->codigo, $pedidoCodigo) !== false) {
                    $score += 15;
                }

                // SCORING 3: Cantidad pendiente
                if ($cantidadPendiente >= $pesoTotal) {
                    $score += 10;
                }

                // SCORING 4: Antigüedad
                $diasDesdeCreacion = $linea->pedido->created_at->diffInDays(now());
                if ($diasDesdeCreacion > 7) {
                    $score += 5;
                }

                $productoDescripcion = $linea->productoBase->nombre ?? null;
                if (!$productoDescripcion && $diametroLinea) {
                    $productoDescripcion = "Ø{$diametroLinea}mm";
                } elseif (!$productoDescripcion) {
                    $productoDescripcion = "ProductoBase #{$linea->producto_base_id} (no encontrado)";
                }

                // Indicar si coincide con diámetros escaneados
                $coincideDiametro = $diametroLinea && in_array($diametroLinea, $diametrosEscaneados);

                return [
                    'id' => $linea->id,
                    'pedido_codigo' => $linea->pedido->codigo ?? '(sin código)',
                    'fabricante' => $fabricante,
                    'obra' => $linea->obra->nombre ?? $linea->obra_manual ?? '(sin obra)',
                    'producto' => $productoDescripcion,
                    'diametro' => $diametroLinea,
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

        // Preparar productos escaneados para mostrar
        $productosEscaneados = collect($productos)->map(function($prod, $index) {
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
                'bultos' => $item['bultos'] ?? 1,
                'peso_kg' => $item['peso_kg'] ?? null,
                'producto_descripcion' => $item['producto_descripcion'] ?? '—',
                'producto_diametro' => $item['producto_diametro'] ?? '—',
                'producto_calidad' => $item['producto_calidad'] ?? '—',
                'estado_simulado' => 'Se crearía',
            ];
        })->values()->toArray();

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
            'hay_coincidencias' => count($lineasConScoring) > 0,
            'bultos_albaran' => $bultosTotal,
            'bultos_simulados' => $bultosSimulados,
        ];
    }

    protected function obtenerNombreProveedor(?string $codigo): string
    {
        return match($codigo) {
            'siderurgica' => 'Siderúrgica Sevillana (SISE)',
            'megasa' => 'Megasa',
            'balboa' => 'Balboa',
            default => 'Otro / No identificado',
        };
    }
}
