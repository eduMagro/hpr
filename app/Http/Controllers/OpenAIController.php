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
     * Genera una simulación de lo que pasaría si se aceptara este albarán
     */
    protected function generarSimulacion(array $parsed): array
    {
        $proveedorNombre = $this->obtenerNombreProveedor($parsed['proveedor'] ?? null);
        $productoBaseId = $parsed['producto']['producto_base_id'] ?? null;
        $lineItems = $parsed['line_items'] ?? [];

        // Buscar fabricante o distribuidor
        $fabricanteId = null;
        $distribuidorId = null;

        if ($parsed['proveedor'] === 'siderurgica') {
            $fabricante = \App\Models\Fabricante::where('nombre', 'LIKE', '%Siderurgica%')
                ->orWhere('nombre', 'LIKE', '%SISE%')
                ->first();
            $fabricanteId = $fabricante?->id;
        } elseif ($parsed['proveedor'] === 'megasa') {
            $distribuidor = \App\Models\Distribuidor::where('nombre', 'LIKE', '%Megasa%')->first();
            $distribuidorId = $distribuidor?->id;
        } elseif ($parsed['proveedor'] === 'balboa') {
            $distribuidor = \App\Models\Distribuidor::where('nombre', 'LIKE', '%Balboa%')->first();
            $distribuidorId = $distribuidor?->id;
        }

        // Buscar líneas de pedido pendientes que coincidan
        $lineasPendientes = \App\Models\PedidoProducto::query()
            ->with(['pedido.fabricante', 'pedido.distribuidor', 'productoBase', 'obra'])
            ->whereHas('pedido', function($q) use ($fabricanteId, $distribuidorId) {
                if ($fabricanteId) {
                    $q->where('fabricante_id', $fabricanteId);
                } elseif ($distribuidorId) {
                    $q->where('distribuidor_id', $distribuidorId);
                }
            })
            ->whereNotIn('estado', ['completado', 'cancelado', 'facturado'])
            ->when($productoBaseId, function($q) use ($productoBaseId) {
                $q->where('producto_base_id', $productoBaseId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Preparar información de líneas pendientes
        $lineasInfo = $lineasPendientes->map(function($linea) {
            return [
                'id' => $linea->id,
                'codigo' => $linea->codigo,
                'pedido_codigo' => $linea->pedido->codigo ?? '(sin código)',
                'producto' => $linea->productoBase->nombre ?? '(sin producto)',
                'cantidad' => $linea->cantidad,
                'cantidad_recepcionada' => $linea->cantidad_recepcionada,
                'cantidad_pendiente' => $linea->cantidad - $linea->cantidad_recepcionada,
                'obra' => $linea->obra->obra ?? $linea->obra_manual ?? '(sin obra)',
                'estado' => $linea->estado,
                'fecha_creacion' => $linea->created_at->format('d/m/Y'),
                'fecha_estimada' => $linea->fecha_estimada_entrega ? \Carbon\Carbon::parse($linea->fecha_estimada_entrega)->format('d/m/Y') : null,
            ];
        })->toArray();

        // Línea propuesta (la más antigua)
        $lineaPropuesta = $lineasInfo[0] ?? null;

        // Calcular bultos totales del albarán
        $bultosTotal = collect($lineItems)->sum('bultos') ?: 0;
        $pesoTotal = $parsed['peso_total'] ?? null;

        // Preparar simulación de bultos a crear
        $bultosSimulados = collect($lineItems)->map(function($item, $index) use ($lineaPropuesta) {
            return [
                'numero' => $index + 1,
                'colada' => $item['colada'] ?? '(sin colada)',
                'peso_kg' => $item['peso_kg'] ?? null,
                'estado_simulado' => 'Se crearía',
                'pedido_producto_id_simulado' => $lineaPropuesta['id'] ?? null,
            ];
        })->toArray();

        // Estado final simulado de la línea
        $estadoFinalSimulado = null;
        if ($lineaPropuesta) {
            $nuevaCantidadRecepcionada = $lineaPropuesta['cantidad_recepcionada'] + $bultosTotal;
            $nuevoEstado = $nuevaCantidadRecepcionada >= $lineaPropuesta['cantidad'] ? 'completado' : 'activo';

            $estadoFinalSimulado = [
                'cantidad_recepcionada_nueva' => $nuevaCantidadRecepcionada,
                'cantidad_total' => $lineaPropuesta['cantidad'],
                'estado_nuevo' => $nuevoEstado,
                'progreso' => round(($nuevaCantidadRecepcionada / $lineaPropuesta['cantidad']) * 100, 1),
            ];
        }

        return [
            'proveedor_nombre' => $proveedorNombre,
            'fabricante_id' => $fabricanteId,
            'distribuidor_id' => $distribuidorId,
            'lineas_pendientes' => $lineasInfo,
            'linea_propuesta' => $lineaPropuesta,
            'bultos_albaran' => $bultosTotal,
            'peso_total' => $pesoTotal,
            'bultos_simulados' => $bultosSimulados,
            'estado_final_simulado' => $estadoFinalSimulado,
            'hay_coincidencias' => count($lineasInfo) > 0,
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
