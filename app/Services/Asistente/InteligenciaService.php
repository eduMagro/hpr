<?php

namespace App\Services\Asistente;

use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\Producto;
use App\Models\ProductoBase;
use App\Models\Pedido;
use App\Models\Alerta;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class InteligenciaService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Obtiene sugerencias proactivas basadas en el estado actual del sistema
     */
    public function obtenerSugerenciasProactivas(int $userId): array
    {
        $sugerencias = [];

        // 1. Stock crítico
        $stockCritico = $this->detectarStockCritico();
        if (!empty($stockCritico)) {
            $sugerencias[] = [
                'tipo' => 'stock_critico',
                'prioridad' => 'alta',
                'icono' => '⚠️',
                'titulo' => 'Stock bajo mínimo',
                'mensaje' => count($stockCritico) . ' producto(s) por debajo del mínimo. '
                    . 'El más crítico: Ø' . ($stockCritico[0]['diametro'] ?? '?') . 'mm al '
                    . ($stockCritico[0]['porcentaje'] ?? 0) . '%.',
                'accion' => 'Ver informe de stock crítico',
                'comando' => 'informe stock crítico',
            ];
        }

        // 2. Planillas atrasadas
        $planillasAtrasadas = $this->detectarPlanillasAtrasadas();
        if ($planillasAtrasadas['total'] > 0) {
            $sugerencias[] = [
                'tipo' => 'planillas_atrasadas',
                'prioridad' => 'alta',
                'icono' => '🔴',
                'titulo' => 'Planillas atrasadas',
                'mensaje' => "{$planillasAtrasadas['total']} planilla(s) han superado su fecha de entrega. "
                    . "Total: " . number_format($planillasAtrasadas['kilos'], 0, ',', '.') . " kg pendientes.",
                'accion' => 'Ver planillas pendientes',
                'comando' => 'informe planillas pendientes',
            ];
        }

        // 3. Planillas urgentes (próximas a vencer)
        $planillasUrgentes = $this->detectarPlanillasUrgentes();
        if ($planillasUrgentes['total'] > 0 && $planillasAtrasadas['total'] === 0) {
            $sugerencias[] = [
                'tipo' => 'planillas_urgentes',
                'prioridad' => 'media',
                'icono' => '🟡',
                'titulo' => 'Entregas próximas',
                'mensaje' => "{$planillasUrgentes['total']} planilla(s) deben entregarse en los próximos 3 días.",
                'accion' => 'Ver planillas urgentes',
                'comando' => 'planillas que entregar esta semana',
            ];
        }

        // 4. Producción del día
        $produccionHoy = $this->obtenerResumenProduccionHoy();
        if ($produccionHoy['kilos'] > 0) {
            $tendencia = $produccionHoy['variacion'] >= 0 ? '📈' : '📉';
            $sugerencias[] = [
                'tipo' => 'produccion_hoy',
                'prioridad' => 'info',
                'icono' => $tendencia,
                'titulo' => 'Producción de hoy',
                'mensaje' => number_format($produccionHoy['kilos'], 0, ',', '.') . " kg fabricados. "
                    . ($produccionHoy['variacion'] >= 0 ? '+' : '') . "{$produccionHoy['variacion']}% vs ayer.",
                'accion' => 'Ver detalle de producción',
                'comando' => 'producción de hoy',
            ];
        }

        // 5. Pedidos sin recepcionar
        $pedidosPendientes = $this->detectarPedidosSinRecepcionar();
        if ($pedidosPendientes['total'] > 0) {
            $sugerencias[] = [
                'tipo' => 'pedidos_pendientes',
                'prioridad' => 'media',
                'icono' => '📦',
                'titulo' => 'Pedidos por recepcionar',
                'mensaje' => "{$pedidosPendientes['total']} pedido(s) activado(s) pendiente(s) de recepción.",
                'accion' => 'Ver pedidos pendientes',
                'comando' => 'pedidos pendientes de recepcionar',
            ];
        }

        // 6. Alertas sin leer
        $alertasSinLeer = $this->contarAlertasSinLeer($userId);
        if ($alertasSinLeer > 0) {
            $sugerencias[] = [
                'tipo' => 'alertas',
                'prioridad' => 'info',
                'icono' => '🔔',
                'titulo' => 'Alertas pendientes',
                'mensaje' => "Tienes {$alertasSinLeer} alerta(s) sin leer.",
                'accion' => 'Ver alertas',
                'comando' => 'mis alertas',
            ];
        }

        // Ordenar por prioridad
        usort($sugerencias, function ($a, $b) {
            $prioridades = ['alta' => 0, 'media' => 1, 'info' => 2];
            return ($prioridades[$a['prioridad']] ?? 3) <=> ($prioridades[$b['prioridad']] ?? 3);
        });

        return $sugerencias;
    }

    /**
     * Analiza tendencias de producción
     */
    public function analizarTendencias(string $periodo = 'semana'): array
    {
        $fechaFin = today();
        $fechaInicio = match ($periodo) {
            'dia' => today(),
            'semana' => today()->subWeek(),
            'mes' => today()->subMonth(),
            'trimestre' => today()->subMonths(3),
            default => today()->subWeek(),
        };

        // Producción por día
        $produccionDiaria = Elemento::query()
            ->selectRaw('DATE(updated_at) as fecha')
            ->selectRaw('SUM(peso) as kilos')
            ->selectRaw('COUNT(*) as elementos')
            ->fabricado()
            ->whereBetween('updated_at', [$fechaInicio, $fechaFin->endOfDay()])
            ->groupBy(DB::raw('DATE(updated_at)'))
            ->orderBy('fecha')
            ->get();

        // Calcular tendencia (regresión lineal simple)
        $dias = $produccionDiaria->count();
        if ($dias < 2) {
            return [
                'tendencia' => 'sin_datos',
                'mensaje' => 'No hay suficientes datos para calcular tendencia.',
                'datos' => [],
            ];
        }

        $kilosArray = $produccionDiaria->pluck('kilos')->toArray();
        $promedio = array_sum($kilosArray) / count($kilosArray);
        $primeraMitad = array_slice($kilosArray, 0, (int) ceil(count($kilosArray) / 2));
        $segundaMitad = array_slice($kilosArray, (int) ceil(count($kilosArray) / 2));

        $promPrimeraMitad = count($primeraMitad) > 0 ? array_sum($primeraMitad) / count($primeraMitad) : 0;
        $promSegundaMitad = count($segundaMitad) > 0 ? array_sum($segundaMitad) / count($segundaMitad) : 0;

        $variacion = $promPrimeraMitad > 0
            ? (($promSegundaMitad - $promPrimeraMitad) / $promPrimeraMitad) * 100
            : 0;

        $tendencia = $variacion > 5 ? 'ascendente' : ($variacion < -5 ? 'descendente' : 'estable');

        return [
            'tendencia' => $tendencia,
            'variacion_porcentaje' => round($variacion, 1),
            'promedio_diario' => round($promedio, 2),
            'total_periodo' => array_sum($kilosArray),
            'dias_analizados' => $dias,
            'periodo' => $periodo,
            'mensaje' => $this->generarMensajeTendencia($tendencia, $variacion, $promedio),
            'datos_diarios' => $produccionDiaria->map(fn($d) => [
                'fecha' => Carbon::parse($d->fecha)->format('d/m'),
                'kilos' => round($d->kilos, 2),
                'elementos' => $d->elementos,
            ])->toArray(),
        ];
    }

    /**
     * Compara dos periodos de tiempo
     */
    public function compararPeriodos(string $periodoActual = 'mes', string $periodoAnterior = 'mes_anterior'): array
    {
        // Determinar fechas
        $hoy = today();

        switch ($periodoActual) {
            case 'semana':
                $inicioActual = $hoy->copy()->startOfWeek();
                $finActual = $hoy;
                $inicioAnterior = $hoy->copy()->subWeek()->startOfWeek();
                $finAnterior = $hoy->copy()->subWeek()->endOfWeek();
                $nombreActual = 'Esta semana';
                $nombreAnterior = 'Semana anterior';
                break;
            case 'mes':
            default:
                $inicioActual = $hoy->copy()->startOfMonth();
                $finActual = $hoy;
                $inicioAnterior = $hoy->copy()->subMonth()->startOfMonth();
                $finAnterior = $hoy->copy()->subMonth()->endOfMonth();
                $nombreActual = 'Este mes';
                $nombreAnterior = 'Mes anterior';
                break;
        }

        // Obtener datos de ambos periodos
        $datosActual = $this->obtenerDatosPeriodo($inicioActual, $finActual);
        $datosAnterior = $this->obtenerDatosPeriodo($inicioAnterior, $finAnterior);

        // Calcular variaciones
        $variaciones = [];
        foreach (['kilos_fabricados', 'elementos_fabricados', 'planillas_completadas'] as $metrica) {
            $actual = $datosActual[$metrica] ?? 0;
            $anterior = $datosAnterior[$metrica] ?? 0;
            $variaciones[$metrica] = $anterior > 0
                ? round((($actual - $anterior) / $anterior) * 100, 1)
                : ($actual > 0 ? 100 : 0);
        }

        return [
            'periodo_actual' => [
                'nombre' => $nombreActual,
                'inicio' => $inicioActual->format('d/m/Y'),
                'fin' => $finActual->format('d/m/Y'),
                'datos' => $datosActual,
            ],
            'periodo_anterior' => [
                'nombre' => $nombreAnterior,
                'inicio' => $inicioAnterior->format('d/m/Y'),
                'fin' => $finAnterior->format('d/m/Y'),
                'datos' => $datosAnterior,
            ],
            'variaciones' => $variaciones,
            'resumen' => $this->generarResumenComparativa($datosActual, $datosAnterior, $variaciones),
        ];
    }

    /**
     * Detecta productos con stock crítico
     */
    protected function detectarStockCritico(): array
    {
        try {
            $stockData = $this->stockService->obtenerDatosStock();

            $minimosStock = [
                6 => 5000, 8 => 10000, 10 => 15000, 12 => 20000,
                16 => 15000, 20 => 10000, 25 => 8000, 32 => 5000,
            ];

            $criticos = [];

            foreach ($stockData['stockData'] as $diametro => $info) {
                $total = ($info['encarretado'] ?? 0) + ($info['barras_total'] ?? 0);
                $minimo = $minimosStock[$diametro] ?? 5000;

                if ($total < $minimo) {
                    $porcentaje = $minimo > 0 ? round(($total / $minimo) * 100, 1) : 0;
                    $criticos[] = [
                        'diametro' => $diametro,
                        'stock' => round($total, 2),
                        'minimo' => $minimo,
                        'porcentaje' => $porcentaje,
                    ];
                }
            }

            // Ordenar por porcentaje (más crítico primero)
            usort($criticos, fn($a, $b) => $a['porcentaje'] <=> $b['porcentaje']);

            return $criticos;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Detecta planillas atrasadas
     */
    protected function detectarPlanillasAtrasadas(): array
    {
        $atrasadas = Planilla::query()
            ->whereIn('estado', ['pendiente', 'fabricando'])
            ->where('fecha_estimada_entrega', '<', today())
            ->selectRaw('COUNT(*) as total, SUM(peso_total) as kilos')
            ->first();

        return [
            'total' => $atrasadas->total ?? 0,
            'kilos' => round($atrasadas->kilos ?? 0, 2),
        ];
    }

    /**
     * Detecta planillas urgentes (próximos 3 días)
     */
    protected function detectarPlanillasUrgentes(): array
    {
        $urgentes = Planilla::query()
            ->whereIn('estado', ['pendiente', 'fabricando'])
            ->whereBetween('fecha_estimada_entrega', [today(), today()->addDays(3)])
            ->selectRaw('COUNT(*) as total, SUM(peso_total) as kilos')
            ->first();

        return [
            'total' => $urgentes->total ?? 0,
            'kilos' => round($urgentes->kilos ?? 0, 2),
        ];
    }

    /**
     * Obtiene resumen de producción de hoy
     */
    protected function obtenerResumenProduccionHoy(): array
    {
        $hoy = Elemento::query()
            ->fabricado()
            ->whereDate('updated_at', today())
            ->selectRaw('SUM(peso) as kilos, COUNT(*) as elementos')
            ->first();

        $ayer = Elemento::query()
            ->fabricado()
            ->whereDate('updated_at', today()->subDay())
            ->sum('peso');

        $kilosHoy = $hoy->kilos ?? 0;
        $variacion = $ayer > 0 ? round((($kilosHoy - $ayer) / $ayer) * 100, 1) : 0;

        return [
            'kilos' => round($kilosHoy, 2),
            'elementos' => $hoy->elementos ?? 0,
            'kilos_ayer' => round($ayer, 2),
            'variacion' => $variacion,
        ];
    }

    /**
     * Detecta pedidos sin recepcionar
     */
    protected function detectarPedidosSinRecepcionar(): array
    {
        // Buscar líneas de pedido activadas pero no recepcionadas
        $pendientes = DB::table('pedido_productos')
            ->where('estado', 'activo')
            ->count();

        return [
            'total' => $pendientes,
        ];
    }

    /**
     * Cuenta alertas sin leer del usuario
     */
    protected function contarAlertasSinLeer(int $userId): int
    {
        try {
            return DB::table('alertas_users')
                ->where('user_id', $userId)
                ->where('leida', false)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtiene datos de producción de un periodo
     */
    protected function obtenerDatosPeriodo(Carbon $inicio, Carbon $fin): array
    {
        $kilos = Elemento::query()
            ->fabricado()
            ->whereBetween('updated_at', [$inicio, $fin->endOfDay()])
            ->sum('peso');

        $elementos = Elemento::query()
            ->fabricado()
            ->whereBetween('updated_at', [$inicio, $fin->endOfDay()])
            ->count();

        $planillas = Planilla::query()
            ->where('estado', 'completada')
            ->whereBetween('fecha_finalizacion', [$inicio, $fin->endOfDay()])
            ->count();

        return [
            'kilos_fabricados' => round($kilos, 2),
            'elementos_fabricados' => $elementos,
            'planillas_completadas' => $planillas,
        ];
    }

    /**
     * Genera mensaje de tendencia
     */
    protected function generarMensajeTendencia(string $tendencia, float $variacion, float $promedio): string
    {
        $promedioFormateado = number_format($promedio, 0, ',', '.');

        return match ($tendencia) {
            'ascendente' => "📈 Tendencia ascendente (+{$variacion}%). Promedio: {$promedioFormateado} kg/día.",
            'descendente' => "📉 Tendencia descendente ({$variacion}%). Promedio: {$promedioFormateado} kg/día.",
            'estable' => "➡️ Producción estable. Promedio: {$promedioFormateado} kg/día.",
            default => "Sin datos suficientes para determinar tendencia.",
        };
    }

    /**
     * Genera resumen de comparativa
     */
    protected function generarResumenComparativa(array $actual, array $anterior, array $variaciones): string
    {
        $mensajes = [];

        if ($variaciones['kilos_fabricados'] > 0) {
            $mensajes[] = "📈 Producción +{$variaciones['kilos_fabricados']}%";
        } elseif ($variaciones['kilos_fabricados'] < 0) {
            $mensajes[] = "📉 Producción {$variaciones['kilos_fabricados']}%";
        } else {
            $mensajes[] = "➡️ Producción estable";
        }

        if ($variaciones['planillas_completadas'] > 0) {
            $mensajes[] = "✅ +{$variaciones['planillas_completadas']}% planillas completadas";
        }

        return implode('. ', $mensajes) . '.';
    }
}
