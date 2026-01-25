<?php

namespace App\Services\Asistente;

use App\Models\AsistenteInforme;
use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\Producto;
use App\Models\ProductoBase;
use App\Models\Salida;
use App\Models\User;
use App\Models\Maquina;
use App\Models\Movimiento;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class InformeService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Genera un informe según el tipo solicitado
     */
    public function generarInforme(string $tipo, int $userId, array $parametros = [], ?int $mensajeId = null): AsistenteInforme
    {
        $metodo = 'generar' . str_replace('_', '', ucwords($tipo, '_'));

        if (!method_exists($this, $metodo)) {
            throw new \InvalidArgumentException("Tipo de informe no válido: {$tipo}");
        }

        $resultado = $this->$metodo($parametros);

        // Calcular fecha de expiración explícitamente (24 horas desde ahora)
        // Usamos formato string para evitar problemas de timezone con TIMESTAMP
        $expiraAt = Carbon::now()->addHours(24)->format('Y-m-d H:i:s');

        $informe = AsistenteInforme::create([
            'user_id' => $userId,
            'mensaje_id' => $mensajeId,
            'tipo' => $tipo,
            'titulo' => $resultado['titulo'],
            'parametros' => $parametros,
            'datos' => $resultado['datos'],
            'resumen' => $resultado['resumen'] ?? null,
            'expira_at' => $expiraAt,
        ]);

        return $informe;
    }

    /**
     * Stock General - Stock por diámetro, tipo, nave
     */
    protected function generarStockGeneral(array $parametros): array
    {
        $naveId = $parametros['nave_id'] ?? null;

        $stockData = $this->stockService->obtenerDatosStock($naveId ? [$naveId] : null);

        // Procesar datos para el informe
        $datos = [];
        $totalGeneral = 0;

        foreach ($stockData['stockData'] as $diametro => $info) {
            $total = ($info['encarretado'] ?? 0) + ($info['barras_total'] ?? 0);
            $totalGeneral += $total;

            $datos[] = [
                'diametro' => $diametro,
                'encarretado' => round($info['encarretado'] ?? 0, 2),
                'barras' => round($info['barras_total'] ?? 0, 2),
                'total' => round($total, 2),
            ];
        }

        return [
            'titulo' => 'Informe de Stock General',
            'datos' => [
                'fecha' => now()->format('d/m/Y H:i'),
                'stock_por_diametro' => $datos,
                'total_general' => round($totalGeneral, 2),
            ],
            'resumen' => [
                'total_kg' => round($totalGeneral, 2),
                'diametros_con_stock' => count(array_filter($datos, fn($d) => $d['total'] > 0)),
                'diametro_mayor_stock' => collect($datos)->sortByDesc('total')->first()['diametro'] ?? null,
            ],
        ];
    }

    /**
     * Stock Crítico - Productos bajo mínimo con recomendaciones
     */
    protected function generarStockCritico(array $parametros): array
    {
        $stockData = $this->stockService->obtenerDatosStock();

        // Mínimos por diámetro (configurables, aquí valores por defecto)
        $minimosStock = [
            6 => 5000,
            8 => 10000,
            10 => 15000,
            12 => 20000,
            16 => 15000,
            20 => 10000,
            25 => 8000,
            32 => 5000,
        ];

        $datosCriticos = [];

        foreach ($stockData['stockData'] as $diametro => $info) {
            $total = ($info['encarretado'] ?? 0) + ($info['barras_total'] ?? 0);
            $minimo = $minimosStock[$diametro] ?? 5000;

            if ($total < $minimo) {
                $reponer = $minimo - $total;
                $porcentaje = $minimo > 0 ? round(($total / $minimo) * 100, 1) : 0;

                $datosCriticos[] = [
                    'diametro' => $diametro,
                    'stock_actual' => round($total, 2),
                    'minimo' => $minimo,
                    'reponer' => round($reponer, 2),
                    'porcentaje' => $porcentaje,
                    'urgencia' => $porcentaje < 30 ? 'alta' : ($porcentaje < 60 ? 'media' : 'baja'),
                ];
            }
        }

        // Ordenar por urgencia
        usort($datosCriticos, fn($a, $b) => $a['porcentaje'] <=> $b['porcentaje']);

        // Calcular tendencias de consumo
        $consumos = $stockData['consumosPorMes'] ?? [];
        $recomendaciones = [];

        foreach ($datosCriticos as $item) {
            $d = $item['diametro'];
            $consumoMensual = 0;

            // Estimar consumo mensual basado en datos disponibles
            if (isset($stockData['recomendacionReposicion'])) {
                foreach ($stockData['recomendacionReposicion'] as $rec) {
                    if (($rec['diametro'] ?? 0) == $d) {
                        $consumoMensual += $rec['tendencia'] ?? 0;
                    }
                }
            }

            $recomendaciones[] = [
                'diametro' => $d,
                'prioridad' => $item['urgencia'],
                'mensaje' => $item['urgencia'] === 'alta'
                    ? "Priorizar pedido de Ø{$d}mm. Stock muy bajo."
                    : "Planificar reposición de Ø{$d}mm.",
                'consumo_estimado_mensual' => round($consumoMensual, 2),
            ];
        }

        return [
            'titulo' => 'Informe de Stock Crítico',
            'datos' => [
                'fecha' => now()->format('d/m/Y H:i'),
                'productos_criticos' => $datosCriticos,
                'recomendaciones' => $recomendaciones,
            ],
            'resumen' => [
                'total_criticos' => count($datosCriticos),
                'urgencia_alta' => count(array_filter($datosCriticos, fn($d) => $d['urgencia'] === 'alta')),
                'urgencia_media' => count(array_filter($datosCriticos, fn($d) => $d['urgencia'] === 'media')),
            ],
        ];
    }

    /**
     * Producción Diaria - Kilos fabricados hoy por máquina
     */
    protected function generarProduccionDiaria(array $parametros): array
    {
        $fecha = isset($parametros['fecha']) ? Carbon::parse($parametros['fecha']) : today();

        // Kilos fabricados por máquina hoy
        $produccionPorMaquina = Elemento::query()
            ->select('maquinas.id', 'maquinas.nombre', 'maquinas.codigo')
            ->selectRaw('SUM(elementos.peso) as kilos_fabricados')
            ->selectRaw('COUNT(elementos.id) as elementos_fabricados')
            ->join('maquinas', 'elementos.maquina_id', '=', 'maquinas.id')
            ->where('elementos.elaborado', 1)
            ->whereDate('elementos.updated_at', $fecha)
            ->groupBy('maquinas.id', 'maquinas.nombre', 'maquinas.codigo')
            ->orderByDesc('kilos_fabricados')
            ->get()
            ->map(fn($row) => [
                'maquina_id' => $row->id,
                'maquina' => $row->nombre ?? $row->codigo,
                'kilos' => round($row->kilos_fabricados ?? 0, 2),
                'elementos' => $row->elementos_fabricados ?? 0,
            ])
            ->toArray();

        $totalKilos = array_sum(array_column($produccionPorMaquina, 'kilos'));
        $totalElementos = array_sum(array_column($produccionPorMaquina, 'elementos'));

        // Comparativa con ayer
        $produccionAyer = Elemento::query()
            ->where('elaborado', 1)
            ->whereDate('updated_at', $fecha->copy()->subDay())
            ->sum('peso');

        $variacion = $produccionAyer > 0
            ? round((($totalKilos - $produccionAyer) / $produccionAyer) * 100, 1)
            : 0;

        return [
            'titulo' => 'Informe de Producción Diaria - ' . $fecha->format('d/m/Y'),
            'datos' => [
                'fecha' => $fecha->format('d/m/Y'),
                'produccion_por_maquina' => $produccionPorMaquina,
                'total_kilos' => round($totalKilos, 2),
                'total_elementos' => $totalElementos,
                'comparativa_ayer' => [
                    'kilos_ayer' => round($produccionAyer, 2),
                    'variacion_porcentaje' => $variacion,
                ],
            ],
            'resumen' => [
                'total_kilos' => round($totalKilos, 2),
                'total_elementos' => $totalElementos,
                'maquinas_activas' => count($produccionPorMaquina),
                'mejor_maquina' => $produccionPorMaquina[0]['maquina'] ?? 'N/A',
                'tendencia' => $variacion >= 0 ? 'positiva' : 'negativa',
            ],
        ];
    }

    /**
     * Producción Semanal - Resumen semanal con comparativa
     */
    protected function generarProduccionSemanal(array $parametros): array
    {
        $fechaFin = isset($parametros['fecha']) ? Carbon::parse($parametros['fecha']) : today();
        $fechaInicio = $fechaFin->copy()->startOfWeek();

        // Producción por día de la semana
        $produccionPorDia = Elemento::query()
            ->selectRaw('DATE(updated_at) as fecha')
            ->selectRaw('SUM(peso) as kilos')
            ->selectRaw('COUNT(*) as elementos')
            ->where('elaborado', 1)
            ->whereBetween('updated_at', [$fechaInicio, $fechaFin->endOfDay()])
            ->groupBy(DB::raw('DATE(updated_at)'))
            ->orderBy('fecha')
            ->get()
            ->map(fn($row) => [
                'fecha' => Carbon::parse($row->fecha)->format('d/m (l)'),
                'fecha_raw' => $row->fecha,
                'kilos' => round($row->kilos ?? 0, 2),
                'elementos' => $row->elementos ?? 0,
            ])
            ->toArray();

        // Producción por máquina en la semana
        $produccionPorMaquina = Elemento::query()
            ->select('maquinas.nombre', 'maquinas.codigo')
            ->selectRaw('SUM(elementos.peso) as kilos')
            ->selectRaw('COUNT(elementos.id) as elementos')
            ->join('maquinas', 'elementos.maquina_id', '=', 'maquinas.id')
            ->where('elementos.elaborado', 1)
            ->whereBetween('elementos.updated_at', [$fechaInicio, $fechaFin->endOfDay()])
            ->groupBy('maquinas.id', 'maquinas.nombre', 'maquinas.codigo')
            ->orderByDesc('kilos')
            ->get()
            ->map(fn($row) => [
                'maquina' => $row->nombre ?? $row->codigo,
                'kilos' => round($row->kilos ?? 0, 2),
                'elementos' => $row->elementos ?? 0,
            ])
            ->toArray();

        $totalKilosSemana = array_sum(array_column($produccionPorDia, 'kilos'));
        $totalElementosSemana = array_sum(array_column($produccionPorDia, 'elementos'));

        // Comparativa con semana anterior
        $semanaAnteriorInicio = $fechaInicio->copy()->subWeek();
        $semanaAnteriorFin = $fechaInicio->copy()->subDay();

        $kilosSemanaAnterior = Elemento::query()
            ->where('estado', 'fabricado')
            ->whereBetween('updated_at', [$semanaAnteriorInicio, $semanaAnteriorFin->endOfDay()])
            ->sum('peso');

        $variacion = $kilosSemanaAnterior > 0
            ? round((($totalKilosSemana - $kilosSemanaAnterior) / $kilosSemanaAnterior) * 100, 1)
            : 0;

        return [
            'titulo' => 'Informe de Producción Semanal',
            'datos' => [
                'periodo' => $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'),
                'produccion_por_dia' => $produccionPorDia,
                'produccion_por_maquina' => $produccionPorMaquina,
                'total_kilos' => round($totalKilosSemana, 2),
                'total_elementos' => $totalElementosSemana,
                'comparativa_semana_anterior' => [
                    'kilos_semana_anterior' => round($kilosSemanaAnterior, 2),
                    'variacion_porcentaje' => $variacion,
                ],
            ],
            'resumen' => [
                'total_kilos' => round($totalKilosSemana, 2),
                'total_elementos' => $totalElementosSemana,
                'promedio_diario' => round($totalKilosSemana / max(count($produccionPorDia), 1), 2),
                'mejor_dia' => collect($produccionPorDia)->sortByDesc('kilos')->first()['fecha'] ?? 'N/A',
                'tendencia' => $variacion >= 0 ? 'positiva' : 'negativa',
            ],
        ];
    }

    /**
     * Consumo por Máquinas - Consumo de materia prima por máquina
     */
    protected function generarConsumoMaquinas(array $parametros): array
    {
        $fechaInicio = isset($parametros['fecha_inicio'])
            ? Carbon::parse($parametros['fecha_inicio'])
            : today()->subMonth();
        $fechaFin = isset($parametros['fecha_fin'])
            ? Carbon::parse($parametros['fecha_fin'])
            : today();

        // Consumo por máquina (movimientos de productos)
        $consumoPorMaquina = Movimiento::query()
            ->select('maquinas.nombre', 'maquinas.codigo', 'maquinas.tipo')
            ->selectRaw('SUM(productos.peso_inicial) as kilos_consumidos')
            ->selectRaw('COUNT(DISTINCT movimientos.id) as num_movimientos')
            ->join('productos', 'movimientos.producto_id', '=', 'productos.id')
            ->join('maquinas', 'movimientos.maquina_destino', '=', 'maquinas.id')
            ->where('movimientos.tipo', 'movimiento libre')
            ->whereNotNull('movimientos.maquina_destino')
            ->whereBetween('movimientos.fecha_ejecucion', [$fechaInicio, $fechaFin->endOfDay()])
            ->groupBy('maquinas.id', 'maquinas.nombre', 'maquinas.codigo', 'maquinas.tipo')
            ->orderByDesc('kilos_consumidos')
            ->get()
            ->map(fn($row) => [
                'maquina' => $row->nombre ?? $row->codigo,
                'tipo' => $row->tipo,
                'kilos_consumidos' => round($row->kilos_consumidos ?? 0, 2),
                'num_movimientos' => $row->num_movimientos ?? 0,
            ])
            ->toArray();

        // Consumo por diámetro
        $consumoPorDiametro = Movimiento::query()
            ->select('productos_base.diametro')
            ->selectRaw('SUM(productos.peso_inicial) as kilos')
            ->join('productos', 'movimientos.producto_id', '=', 'productos.id')
            ->join('productos_base', 'productos.producto_base_id', '=', 'productos_base.id')
            ->where('movimientos.tipo', 'movimiento libre')
            ->whereNotNull('movimientos.maquina_destino')
            ->whereBetween('movimientos.fecha_ejecucion', [$fechaInicio, $fechaFin->endOfDay()])
            ->groupBy('productos_base.diametro')
            ->orderByDesc('kilos')
            ->get()
            ->map(fn($row) => [
                'diametro' => $row->diametro,
                'kilos' => round($row->kilos ?? 0, 2),
            ])
            ->toArray();

        $totalConsumo = array_sum(array_column($consumoPorMaquina, 'kilos_consumidos'));

        return [
            'titulo' => 'Informe de Consumo por Máquinas',
            'datos' => [
                'periodo' => $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'),
                'consumo_por_maquina' => $consumoPorMaquina,
                'consumo_por_diametro' => $consumoPorDiametro,
                'total_consumido' => round($totalConsumo, 2),
            ],
            'resumen' => [
                'total_consumido' => round($totalConsumo, 2),
                'maquinas_con_consumo' => count($consumoPorMaquina),
                'maquina_mayor_consumo' => $consumoPorMaquina[0]['maquina'] ?? 'N/A',
                'diametro_mas_usado' => $consumoPorDiametro[0]['diametro'] ?? 'N/A',
            ],
        ];
    }

    /**
     * Peso por Obra - Kilos entregados a cada obra
     */
    protected function generarPesoObra(array $parametros): array
    {
        $fechaInicio = isset($parametros['fecha_inicio'])
            ? Carbon::parse($parametros['fecha_inicio'])
            : today()->subMonth();
        $fechaFin = isset($parametros['fecha_fin'])
            ? Carbon::parse($parametros['fecha_fin'])
            : today();

        // Kilos por obra desde salidas
        $kilosPorObra = DB::table('salidas')
            ->select('obras.obra as nombre_obra', 'clientes.empresa as cliente')
            ->selectRaw('SUM(salidas.importe) as importe_total')
            ->selectRaw('COUNT(salidas.id) as num_portes')
            ->join('obras', function($join) {
                // Intentar diferentes formas de relación
                $join->on('salidas.obra_id', '=', 'obras.id');
            })
            ->leftJoin('clientes', 'obras.cliente_id', '=', 'clientes.id')
            ->whereBetween('salidas.fecha_salida', [$fechaInicio, $fechaFin->endOfDay()])
            ->where('salidas.estado', '!=', 'cancelada')
            ->groupBy('obras.id', 'obras.obra', 'clientes.empresa')
            ->orderByDesc('num_portes')
            ->limit(50)
            ->get()
            ->map(fn($row) => [
                'obra' => $row->nombre_obra,
                'cliente' => $row->cliente,
                'num_portes' => $row->num_portes ?? 0,
                'importe' => round($row->importe_total ?? 0, 2),
            ])
            ->toArray();

        // Alternativa: kilos desde planillas completadas por obra
        $kilosPorObraProduccion = Planilla::query()
            ->select('obras.obra as nombre_obra', 'clientes.empresa as cliente')
            ->selectRaw('SUM(planillas.peso_total) as kilos')
            ->selectRaw('COUNT(planillas.id) as num_planillas')
            ->join('obras', 'planillas.obra_id', '=', 'obras.id')
            ->leftJoin('clientes', 'obras.cliente_id', '=', 'clientes.id')
            ->where('planillas.estado', 'completada')
            ->whereBetween('planillas.fecha_finalizacion', [$fechaInicio, $fechaFin->endOfDay()])
            ->groupBy('obras.id', 'obras.obra', 'clientes.empresa')
            ->orderByDesc('kilos')
            ->limit(50)
            ->get()
            ->map(fn($row) => [
                'obra' => $row->nombre_obra,
                'cliente' => $row->cliente,
                'kilos' => round($row->kilos ?? 0, 2),
                'num_planillas' => $row->num_planillas ?? 0,
            ])
            ->toArray();

        $totalKilos = array_sum(array_column($kilosPorObraProduccion, 'kilos'));

        return [
            'titulo' => 'Informe de Kilos por Obra',
            'datos' => [
                'periodo' => $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'),
                'kilos_por_obra' => $kilosPorObraProduccion,
                'salidas_por_obra' => $kilosPorObra,
                'total_kilos' => round($totalKilos, 2),
            ],
            'resumen' => [
                'total_kilos' => round($totalKilos, 2),
                'obras_con_produccion' => count($kilosPorObraProduccion),
                'obra_mayor_produccion' => $kilosPorObraProduccion[0]['obra'] ?? 'N/A',
            ],
        ];
    }

    /**
     * Planilleros - Producción por usuario (operarios de planillas)
     */
    protected function generarPlanilleros(array $parametros): array
    {
        $fechaInicio = isset($parametros['fecha_inicio'])
            ? Carbon::parse($parametros['fecha_inicio'])
            : today()->subMonth();
        $fechaFin = isset($parametros['fecha_fin'])
            ? Carbon::parse($parametros['fecha_fin'])
            : today();

        // Producción por usuario (basado en elementos fabricados)
        $produccionPorUsuario = Elemento::query()
            ->select('users.id', 'users.name', 'maquinas.nombre as maquina')
            ->selectRaw('SUM(elementos.peso) as kilos')
            ->selectRaw('COUNT(elementos.id) as elementos')
            ->join('users', 'elementos.user_id', '=', 'users.id')
            ->leftJoin('maquinas', 'elementos.maquina_id', '=', 'maquinas.id')
            ->where('elementos.elaborado', 1)
            ->whereBetween('elementos.updated_at', [$fechaInicio, $fechaFin->endOfDay()])
            ->groupBy('users.id', 'users.name', 'maquinas.nombre')
            ->orderByDesc('kilos')
            ->get()
            ->map(fn($row) => [
                'usuario_id' => $row->id,
                'nombre' => $row->name,
                'maquina' => $row->maquina,
                'kilos' => round($row->kilos ?? 0, 2),
                'elementos' => $row->elementos ?? 0,
            ])
            ->toArray();

        // Agrupar por usuario si tiene varias máquinas
        $agrupado = [];
        foreach ($produccionPorUsuario as $item) {
            $uid = $item['usuario_id'];
            if (!isset($agrupado[$uid])) {
                $agrupado[$uid] = [
                    'nombre' => $item['nombre'],
                    'kilos' => 0,
                    'elementos' => 0,
                    'maquinas' => [],
                ];
            }
            $agrupado[$uid]['kilos'] += $item['kilos'];
            $agrupado[$uid]['elementos'] += $item['elementos'];
            if ($item['maquina']) {
                $agrupado[$uid]['maquinas'][] = $item['maquina'];
            }
        }

        $resumenUsuarios = array_values(array_map(fn($u) => [
            'nombre' => $u['nombre'],
            'kilos' => round($u['kilos'], 2),
            'elementos' => $u['elementos'],
            'maquinas' => implode(', ', array_unique($u['maquinas'])),
        ], $agrupado));

        usort($resumenUsuarios, fn($a, $b) => $b['kilos'] <=> $a['kilos']);

        $totalKilos = array_sum(array_column($resumenUsuarios, 'kilos'));

        return [
            'titulo' => 'Informe de Producción por Planillero',
            'datos' => [
                'periodo' => $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'),
                'produccion_por_usuario' => $resumenUsuarios,
                'detalle_por_maquina' => $produccionPorUsuario,
                'total_kilos' => round($totalKilos, 2),
            ],
            'resumen' => [
                'total_kilos' => round($totalKilos, 2),
                'planilleros_activos' => count($resumenUsuarios),
                'mejor_planillero' => $resumenUsuarios[0]['nombre'] ?? 'N/A',
                'promedio_por_planillero' => count($resumenUsuarios) > 0
                    ? round($totalKilos / count($resumenUsuarios), 2)
                    : 0,
            ],
        ];
    }

    /**
     * Planillas Pendientes - Planillas por estado
     */
    protected function generarPlanillasPendientes(array $parametros): array
    {
        // Planillas pendientes ordenadas por urgencia
        $planillasPendientes = Planilla::query()
            ->select(
                'planillas.id',
                'planillas.codigo',
                'planillas.peso_total',
                'planillas.estado',
                'planillas.fecha_estimada_entrega',
                'planillas.created_at',
                'obras.obra as nombre_obra',
                'clientes.empresa as cliente'
            )
            ->leftJoin('obras', 'planillas.obra_id', '=', 'obras.id')
            ->leftJoin('clientes', 'planillas.cliente_id', '=', 'clientes.id')
            ->whereIn('planillas.estado', ['pendiente', 'fabricando'])
            ->orderBy('planillas.fecha_estimada_entrega')
            ->limit(100)
            ->get()
            ->map(function($p) {
                $diasRestantes = $p->fecha_estimada_entrega
                    ? now()->diffInDays(Carbon::parse($p->fecha_estimada_entrega), false)
                    : null;

                return [
                    'codigo' => $p->codigo,
                    'cliente' => $p->cliente,
                    'obra' => $p->nombre_obra,
                    'peso' => round($p->peso_total ?? 0, 2),
                    'estado' => $p->estado,
                    'fecha_entrega' => $p->fecha_estimada_entrega
                        ? Carbon::parse($p->fecha_estimada_entrega)->format('d/m/Y')
                        : 'Sin fecha',
                    'dias_restantes' => $diasRestantes,
                    'urgencia' => $diasRestantes !== null
                        ? ($diasRestantes < 0 ? 'atrasada' : ($diasRestantes <= 3 ? 'urgente' : 'normal'))
                        : 'sin_fecha',
                ];
            })
            ->toArray();

        // Resumen por estado
        $resumenPorEstado = Planilla::query()
            ->selectRaw('estado, COUNT(*) as cantidad, SUM(peso_total) as kilos')
            ->whereIn('estado', ['pendiente', 'fabricando', 'completada'])
            ->groupBy('estado')
            ->get()
            ->mapWithKeys(fn($row) => [
                $row->estado => [
                    'cantidad' => $row->cantidad,
                    'kilos' => round($row->kilos ?? 0, 2),
                ]
            ])
            ->toArray();

        $atrasadas = count(array_filter($planillasPendientes, fn($p) => $p['urgencia'] === 'atrasada'));
        $urgentes = count(array_filter($planillasPendientes, fn($p) => $p['urgencia'] === 'urgente'));

        return [
            'titulo' => 'Informe de Planillas Pendientes',
            'datos' => [
                'fecha' => now()->format('d/m/Y H:i'),
                'planillas' => $planillasPendientes,
                'resumen_por_estado' => $resumenPorEstado,
            ],
            'resumen' => [
                'total_pendientes' => ($resumenPorEstado['pendiente']['cantidad'] ?? 0) +
                                      ($resumenPorEstado['fabricando']['cantidad'] ?? 0),
                'kilos_pendientes' => ($resumenPorEstado['pendiente']['kilos'] ?? 0) +
                                      ($resumenPorEstado['fabricando']['kilos'] ?? 0),
                'atrasadas' => $atrasadas,
                'urgentes' => $urgentes,
            ],
        ];
    }

    /**
     * Formatea el informe como HTML con tablas estilizadas para mostrar en chat
     */
    public function formatearParaChat(AsistenteInforme $informe): string
    {
        $tipo = $informe->tipo;
        $datos = $informe->datos;
        $resumen = $informe->resumen;

        $output = "<div class=\"informe-container\">\n";
        $output .= "<h3 style=\"margin:0 0 12px 0;font-size:1.1rem;font-weight:700;color:#1f2937;\">📊 {$informe->titulo}</h3>\n";

        switch ($tipo) {
            case 'stock_general':
                $output .= "<p style=\"margin:0 0 12px 0;color:#6b7280;font-size:0.875rem;\"><strong>Fecha:</strong> {$datos['fecha']}</p>\n";
                $output .= $this->generarTablaHTML(
                    ['Diámetro', 'Encarretado', 'Barras', 'Total'],
                    array_filter(array_map(function($item) {
                        if ($item['total'] <= 0) return null;
                        return [
                            "<strong>Ø{$item['diametro']}mm</strong>",
                            number_format($item['encarretado'], 0, ',', '.') . ' kg',
                            number_format($item['barras'], 0, ',', '.') . ' kg',
                            "<strong style=\"color:#059669;\">" . number_format($item['total'], 0, ',', '.') . " kg</strong>",
                        ];
                    }, $datos['stock_por_diametro'] ?? []))
                );
                $output .= $this->generarResumenBox([
                    ['label' => 'Total General', 'value' => number_format($resumen['total_kg'] ?? 0, 0, ',', '.') . ' kg', 'color' => '#059669'],
                    ['label' => 'Diámetros', 'value' => $resumen['diametros_con_stock'] ?? 0, 'color' => '#3b82f6'],
                ]);
                break;

            case 'stock_critico':
                if (empty($datos['productos_criticos'])) {
                    $output .= "<div style=\"background:#d1fae5;border:1px solid #10b981;border-radius:8px;padding:12px;margin:8px 0;\">";
                    $output .= "<strong style=\"color:#065f46;\">✅ ¡Excelente!</strong> No hay productos por debajo del mínimo.";
                    $output .= "</div>\n";
                } else {
                    $output .= "<div style=\"background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:12px;margin:0 0 12px 0;\">";
                    $output .= "<strong style=\"color:#92400e;\">⚠️ {$resumen['total_criticos']} producto(s) por debajo del mínimo</strong>";
                    $output .= "</div>\n";

                    $output .= $this->generarTablaHTML(
                        ['Diámetro', 'Stock', 'Mínimo', 'Reponer', 'Urgencia'],
                        array_map(function($item) {
                            $urgenciaStyle = match($item['urgencia']) {
                                'alta' => 'background:#fee2e2;color:#991b1b;',
                                'media' => 'background:#fef3c7;color:#92400e;',
                                default => 'background:#d1fae5;color:#065f46;',
                            };
                            $emoji = match($item['urgencia']) {
                                'alta' => '🔴',
                                'media' => '🟡',
                                default => '🟢',
                            };
                            return [
                                "<strong>Ø{$item['diametro']}mm</strong>",
                                number_format($item['stock_actual'], 0, ',', '.') . ' kg',
                                number_format($item['minimo'], 0, ',', '.') . ' kg',
                                "<strong style=\"color:#dc2626;\">" . number_format($item['reponer'], 0, ',', '.') . " kg</strong>",
                                "<span style=\"{$urgenciaStyle}padding:2px 8px;border-radius:12px;font-size:0.75rem;font-weight:600;\">{$emoji} " . ucfirst($item['urgencia']) . "</span>",
                            ];
                        }, $datos['productos_criticos'] ?? [])
                    );

                    if (!empty($datos['recomendaciones'])) {
                        $output .= "<div style=\"margin-top:12px;\"><strong style=\"font-size:0.875rem;\">💡 Recomendaciones:</strong></div>\n";
                        $output .= "<ul style=\"margin:8px 0;padding-left:20px;font-size:0.875rem;color:#4b5563;\">\n";
                        foreach (array_slice($datos['recomendaciones'], 0, 3) as $rec) {
                            $output .= "<li style=\"margin:4px 0;\">{$rec['mensaje']}</li>\n";
                        }
                        $output .= "</ul>\n";
                    }
                }
                break;

            case 'produccion_diaria':
                $output .= "<p style=\"margin:0 0 12px 0;color:#6b7280;font-size:0.875rem;\"><strong>Fecha:</strong> {$datos['fecha']}</p>\n";

                $output .= $this->generarTablaHTML(
                    ['Máquina', 'Kilos', 'Elementos'],
                    array_map(function($item) {
                        return [
                            "<strong>{$item['maquina']}</strong>",
                            number_format($item['kilos'], 0, ',', '.') . ' kg',
                            $item['elementos'],
                        ];
                    }, $datos['produccion_por_maquina'] ?? [])
                );

                $tendencia = ($datos['comparativa_ayer']['variacion_porcentaje'] ?? 0) >= 0 ? '📈' : '📉';
                $tendenciaColor = ($datos['comparativa_ayer']['variacion_porcentaje'] ?? 0) >= 0 ? '#059669' : '#dc2626';

                $output .= $this->generarResumenBox([
                    ['label' => 'Total Kilos', 'value' => number_format($resumen['total_kilos'] ?? 0, 0, ',', '.') . ' kg', 'color' => '#059669'],
                    ['label' => 'Elementos', 'value' => $resumen['total_elementos'] ?? 0, 'color' => '#3b82f6'],
                    ['label' => "{$tendencia} vs Ayer", 'value' => ($datos['comparativa_ayer']['variacion_porcentaje'] ?? 0) . '%', 'color' => $tendenciaColor],
                ]);
                break;

            case 'produccion_semanal':
                $output .= "<p style=\"margin:0 0 12px 0;color:#6b7280;font-size:0.875rem;\"><strong>Periodo:</strong> {$datos['periodo']}</p>\n";

                $output .= $this->generarTablaHTML(
                    ['Día', 'Kilos', 'Elementos'],
                    array_map(function($item) {
                        return [
                            "<strong>{$item['fecha']}</strong>",
                            number_format($item['kilos'], 0, ',', '.') . ' kg',
                            $item['elementos'] ?? '-',
                        ];
                    }, $datos['produccion_por_dia'] ?? [])
                );

                $tendencia = ($datos['comparativa_semana_anterior']['variacion_porcentaje'] ?? 0) >= 0 ? '📈' : '📉';
                $tendenciaColor = ($datos['comparativa_semana_anterior']['variacion_porcentaje'] ?? 0) >= 0 ? '#059669' : '#dc2626';

                $output .= $this->generarResumenBox([
                    ['label' => 'Total Semana', 'value' => number_format($resumen['total_kilos'] ?? 0, 0, ',', '.') . ' kg', 'color' => '#059669'],
                    ['label' => 'Promedio/Día', 'value' => number_format($resumen['promedio_diario'] ?? 0, 0, ',', '.') . ' kg', 'color' => '#3b82f6'],
                    ['label' => "{$tendencia} vs Anterior", 'value' => ($datos['comparativa_semana_anterior']['variacion_porcentaje'] ?? 0) . '%', 'color' => $tendenciaColor],
                ]);
                break;

            case 'consumo_maquinas':
                $output .= "<p style=\"margin:0 0 12px 0;color:#6b7280;font-size:0.875rem;\"><strong>Periodo:</strong> {$datos['periodo']}</p>\n";

                $output .= $this->generarTablaHTML(
                    ['Máquina', 'Tipo', 'Kilos Consumidos', 'Movimientos'],
                    array_map(function($item) {
                        return [
                            "<strong>{$item['maquina']}</strong>",
                            $item['tipo'] ?? '-',
                            number_format($item['kilos_consumidos'], 0, ',', '.') . ' kg',
                            $item['num_movimientos'],
                        ];
                    }, $datos['consumo_por_maquina'] ?? [])
                );

                $output .= $this->generarResumenBox([
                    ['label' => 'Total Consumido', 'value' => number_format($resumen['total_consumido'] ?? 0, 0, ',', '.') . ' kg', 'color' => '#059669'],
                    ['label' => 'Máquinas Activas', 'value' => $resumen['maquinas_con_consumo'] ?? 0, 'color' => '#3b82f6'],
                ]);
                break;

            case 'planillas_pendientes':
                // Alertas
                if (($resumen['atrasadas'] ?? 0) > 0) {
                    $output .= "<div style=\"background:#fee2e2;border:1px solid #ef4444;border-radius:8px;padding:12px;margin:0 0 8px 0;\">";
                    $output .= "<strong style=\"color:#991b1b;\">🔴 {$resumen['atrasadas']} planilla(s) atrasada(s)</strong>";
                    $output .= "</div>\n";
                }
                if (($resumen['urgentes'] ?? 0) > 0) {
                    $output .= "<div style=\"background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:12px;margin:0 0 12px 0;\">";
                    $output .= "<strong style=\"color:#92400e;\">🟡 {$resumen['urgentes']} planilla(s) urgente(s) (< 3 días)</strong>";
                    $output .= "</div>\n";
                }

                $output .= $this->generarResumenBox([
                    ['label' => 'Total Pendientes', 'value' => $resumen['total_pendientes'] ?? 0, 'color' => '#f59e0b'],
                    ['label' => 'Kilos Pendientes', 'value' => number_format($resumen['kilos_pendientes'] ?? 0, 0, ',', '.') . ' kg', 'color' => '#3b82f6'],
                ]);

                $output .= "<p style=\"margin:12px 0 8px 0;font-weight:600;font-size:0.875rem;\">📋 Planillas más urgentes:</p>\n";
                $output .= $this->generarTablaHTML(
                    ['Código', 'Cliente', 'Peso', 'Entrega', 'Estado'],
                    array_map(function($p) {
                        $urgenciaStyle = match($p['urgencia'] ?? 'normal') {
                            'atrasada' => 'color:#991b1b;font-weight:600;',
                            'urgente' => 'color:#92400e;font-weight:600;',
                            default => '',
                        };
                        $emoji = match($p['urgencia'] ?? 'normal') {
                            'atrasada' => '🔴 ',
                            'urgente' => '🟡 ',
                            default => '',
                        };
                        $estadoBadge = match($p['estado'] ?? 'pendiente') {
                            'fabricando' => '<span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:12px;font-size:0.75rem;">fabricando</span>',
                            'pendiente' => '<span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:12px;font-size:0.75rem;">pendiente</span>',
                            default => $p['estado'],
                        };
                        return [
                            "<strong>{$p['codigo']}</strong>",
                            $p['cliente'] ?? '-',
                            number_format($p['peso'] ?? 0, 0, ',', '.') . ' kg',
                            "<span style=\"{$urgenciaStyle}\">{$emoji}{$p['fecha_entrega']}</span>",
                            $estadoBadge,
                        ];
                    }, array_slice($datos['planillas'] ?? [], 0, 10))
                );
                break;

            case 'peso_obra':
                $output .= "<p style=\"margin:0 0 12px 0;color:#6b7280;font-size:0.875rem;\"><strong>Periodo:</strong> {$datos['periodo']}</p>\n";

                $output .= $this->generarTablaHTML(
                    ['Obra', 'Cliente', 'Kilos', 'Planillas'],
                    array_map(function($item) {
                        return [
                            "<strong>" . mb_substr($item['obra'] ?? 'Sin nombre', 0, 25) . "</strong>",
                            mb_substr($item['cliente'] ?? '-', 0, 20),
                            number_format($item['kilos'] ?? 0, 0, ',', '.') . ' kg',
                            $item['num_planillas'] ?? 0,
                        ];
                    }, array_slice($datos['kilos_por_obra'] ?? [], 0, 10))
                );

                $output .= $this->generarResumenBox([
                    ['label' => 'Total Kilos', 'value' => number_format($resumen['total_kilos'] ?? 0, 0, ',', '.') . ' kg', 'color' => '#059669'],
                    ['label' => 'Obras', 'value' => $resumen['obras_con_produccion'] ?? 0, 'color' => '#3b82f6'],
                ]);
                break;

            case 'planilleros':
                $output .= "<p style=\"margin:0 0 12px 0;color:#6b7280;font-size:0.875rem;\"><strong>Periodo:</strong> {$datos['periodo']}</p>\n";

                $output .= $this->generarTablaHTML(
                    ['#', 'Operario', 'Kilos', 'Elementos'],
                    array_map(function($item, $index) {
                        $medalla = match($index) {
                            0 => '🥇',
                            1 => '🥈',
                            2 => '🥉',
                            default => ($index + 1),
                        };
                        return [
                            "<strong>{$medalla}</strong>",
                            "<strong>{$item['nombre']}</strong>",
                            number_format($item['kilos'] ?? 0, 0, ',', '.') . ' kg',
                            $item['elementos'] ?? 0,
                        ];
                    }, array_slice($datos['produccion_por_usuario'] ?? [], 0, 10), array_keys(array_slice($datos['produccion_por_usuario'] ?? [], 0, 10)))
                );

                $output .= $this->generarResumenBox([
                    ['label' => 'Total Kilos', 'value' => number_format($resumen['total_kilos'] ?? 0, 0, ',', '.') . ' kg', 'color' => '#059669'],
                    ['label' => 'Operarios', 'value' => $resumen['planilleros_activos'] ?? 0, 'color' => '#3b82f6'],
                    ['label' => 'Promedio', 'value' => number_format($resumen['promedio_por_planillero'] ?? 0, 0, ',', '.') . ' kg', 'color' => '#8b5cf6'],
                ]);
                break;

            default:
                $output .= "<p style=\"color:#6b7280;\">Datos del informe disponibles.</p>\n";
                if ($resumen) {
                    $output .= "<div style=\"margin-top:12px;\"><strong>Resumen:</strong></div>\n";
                    $output .= "<ul style=\"margin:8px 0;padding-left:20px;\">\n";
                    foreach ($resumen as $key => $value) {
                        $output .= "<li>" . ucfirst(str_replace('_', ' ', $key)) . ": <strong>{$value}</strong></li>\n";
                    }
                    $output .= "</ul>\n";
                }
        }

        $output .= "</div>";
        return $output;
    }

    /**
     * Genera una tabla HTML con estilos
     */
    protected function generarTablaHTML(array $headers, array $rows): string
    {
        if (empty($rows)) {
            return "<p style=\"color:#6b7280;font-style:italic;\">No hay datos disponibles</p>\n";
        }

        $html = "<div style=\"overflow-x:auto;margin:8px 0;\">\n";
        $html .= "<table style=\"width:100%;border-collapse:collapse;font-size:0.875rem;\">\n";

        // Header
        $html .= "<thead><tr style=\"background:#f3f4f6;\">\n";
        foreach ($headers as $header) {
            $html .= "<th style=\"padding:10px 12px;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;white-space:nowrap;\">{$header}</th>\n";
        }
        $html .= "</tr></thead>\n";

        // Body
        $html .= "<tbody>\n";
        $rowIndex = 0;
        foreach ($rows as $row) {
            if ($row === null) continue;
            $bgColor = $rowIndex % 2 === 0 ? '#ffffff' : '#f9fafb';
            $html .= "<tr style=\"background:{$bgColor};\">\n";
            foreach ($row as $cell) {
                $html .= "<td style=\"padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#4b5563;\">{$cell}</td>\n";
            }
            $html .= "</tr>\n";
            $rowIndex++;
        }
        $html .= "</tbody>\n";

        $html .= "</table>\n</div>\n";
        return $html;
    }

    /**
     * Genera un box de resumen con métricas
     */
    protected function generarResumenBox(array $items): string
    {
        $html = "<div style=\"display:flex;flex-wrap:wrap;gap:8px;margin:12px 0;\">\n";
        foreach ($items as $item) {
            $color = $item['color'] ?? '#3b82f6';
            $html .= "<div style=\"background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;flex:1;min-width:120px;\">\n";
            $html .= "<div style=\"font-size:0.75rem;color:#6b7280;margin-bottom:2px;\">{$item['label']}</div>\n";
            $html .= "<div style=\"font-size:1.1rem;font-weight:700;color:{$color};\">{$item['value']}</div>\n";
            $html .= "</div>\n";
        }
        $html .= "</div>\n";
        return $html;
    }

    /**
     * Detecta si un mensaje solicita un informe
     */
    public function detectarSolicitudInforme(string $mensaje): ?array
    {
        $mensaje = strtolower($mensaje);

        $patrones = [
            'stock_general' => [
                '/informe.*stock/i',
                '/reporte.*stock/i',
                '/stock.*general/i',
                '/genera.*informe.*stock/i',
                '/dame.*informe.*stock/i',
            ],
            'stock_critico' => [
                '/stock.*cr[íi]tico/i',
                '/productos.*bajo.*m[íi]nimo/i',
                '/qu[eé].*falta.*reponer/i',
                '/material.*bajo/i',
                '/informe.*cr[íi]tico/i',
            ],
            'produccion_diaria' => [
                '/producci[oó]n.*hoy/i',
                '/producci[oó]n.*diaria/i',
                '/kilos.*fabricados.*hoy/i',
                '/informe.*producci[oó]n.*d[íi]a/i',
                '/qu[eé].*se.*fabric[oó].*hoy/i',
            ],
            'produccion_semanal' => [
                '/producci[oó]n.*semana/i',
                '/producci[oó]n.*semanal/i',
                '/resumen.*semana/i',
                '/informe.*semanal/i',
            ],
            'consumo_maquinas' => [
                '/consumo.*m[aá]quina/i',
                '/materia.*prima.*m[aá]quina/i',
                '/qu[eé].*consume.*cada.*m[aá]quina/i',
            ],
            'peso_obra' => [
                '/kilos.*obra/i',
                '/peso.*obra/i',
                '/entregado.*obra/i',
                '/producci[oó]n.*por.*obra/i',
            ],
            'planilleros' => [
                '/producci[oó]n.*usuario/i',
                '/producci[oó]n.*planillero/i',
                '/qui[eé]n.*m[aá]s.*produce/i',
                '/rendimiento.*operarios/i',
            ],
            'planillas_pendientes' => [
                '/planillas.*pendientes/i',
                '/planillas.*estado/i',
                '/qu[eé].*planillas.*faltan/i',
                '/planillas.*atrasadas/i',
            ],
        ];

        foreach ($patrones as $tipo => $expresiones) {
            foreach ($expresiones as $patron) {
                if (preg_match($patron, $mensaje)) {
                    return [
                        'tipo' => $tipo,
                        'nombre' => AsistenteInforme::TIPOS[$tipo] ?? $tipo,
                    ];
                }
            }
        }

        return null;
    }
}
