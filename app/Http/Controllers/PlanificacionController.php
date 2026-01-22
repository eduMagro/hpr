<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;
use App\Models\Elemento;
use App\Models\Paquete;
use App\Models\Salida;
use App\Models\Obra;
use App\Models\Cliente;
use App\Models\Camion;
use App\Models\Festivo;
use App\Models\User;
use App\Models\EmpresaTransporte;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AlertaService;
use App\Services\ActionLoggerService;
use App\Services\FinProgramadoService;
use App\Models\Departamento;
use App\Models\SalidaCliente;
use Illuminate\Support\Facades\DB;

class PlanificacionController extends Controller
{
    /**
     * Parsea una fecha flexible aceptando múltiples formatos
     */
    private function parseFlexibleDate($date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        if ($date instanceof Carbon) {
            return $date;
        }

        // Intentar formatos comunes
        $formats = [
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'd-m-Y',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $date);
                if ($parsed && $parsed->format($format) === $date) {
                    return $parsed;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Fallback a parse normal
        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            Log::warning('No se pudo parsear la fecha', ['fecha' => $date, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function index(Request $request)
    {
        // Si es carga inicial de la vista (no AJAX), devolver vista rápida sin eventos
        // Los eventos se cargarán via AJAX por el calendario
        $tipo = $request->input('tipo');
        if (!$tipo) {
            // Vista inicial - NO cargar eventos, el calendario los pedirá via AJAX
            $fechas = collect(range(0, 13))->map(fn($i) => [
                'fecha' => now()->addDays($i)->format('Y-m-d'),
                'dia' => now()->addDays($i)->locale('es')->translatedFormat('l'),
            ]);

            $camiones = Camion::with('empresaTransporte:id,nombre')
                ->get(['id', 'modelo', 'empresa_id']);

            $obras = Obra::where('estado', 'activa')
                ->orderBy('obra')
                ->get(['id', 'obra']);

            $empresasTransporte = EmpresaTransporte::orderBy('nombre')->get(['id', 'nombre']);

            return view('planificacion.index', [
                'fechas' => $fechas,
                'camiones' => $camiones,
                'obras' => $obras,
                'eventos' => collect(), // Vacío - se cargan via AJAX
                'empresasTransporte' => $empresasTransporte,
            ]);
        }

        // ========== PETICIONES AJAX ==========
        [$startDate, $endDate] = $this->getDateRange($request);
        $viewType = $request->input('viewType', 'resourceTimeGridDay');

        // 🚀 Cache: verificar si existe antes de hacer consultas pesadas
        $cacheKey = "planificacion_{$tipo}_{$viewType}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        $cacheTTL = 45; // segundos

        // AJAX: tipo=all devuelve todo en una sola petición
        if ($tipo === 'all') {
            // Intentar obtener de caché primero
            $cached = cache()->get($cacheKey);
            if ($cached) {
                return response()->json($cached);
            }

            // Si no hay caché, generar datos
            $salidasEventos = $this->getEventosSalidas($startDate, $endDate, $viewType);
            $planillasEventos = $this->getEventosPlanillas($startDate, $endDate, $viewType);
            $resumenEventos = $this->getEventosResumen($planillasEventos['eventos'], $viewType);
            $eventos = collect()
                ->concat($planillasEventos['eventos'])
                ->concat($salidasEventos)
                ->concat($resumenEventos)
                ->concat(Festivo::eventosCalendario())
                ->sortBy([
                    fn($e) => match ($e['tipo'] ?? $e['extendedProps']['tipo'] ?? '') {
                        'planilla' => 0,
                        'salida' => 1,
                        'resumen' => 2,
                        'festivo' => 3,
                        default => 99,
                    },
                    fn($e) => $e['extendedProps']['cod_obra'] ?? '',
                ])
                ->values();

            $resources = $this->getResources($eventos, $viewType);

            $fechaReferencia = $this->parseFlexibleDate($request->input('start')) ?? now();
            $startOfWeek = $fechaReferencia->copy()->startOfWeek(Carbon::MONDAY);
            $endOfWeek = $fechaReferencia->copy()->endOfWeek(Carbon::SUNDAY);

            // Calcular totales con consultas optimizadas
            $totalesSemana = Planilla::whereBetween('fecha_estimada_entrega', [$startOfWeek, $endOfWeek])
                ->selectRaw('SUM(peso_total) as peso')
                ->first();

            $totalesMes = Planilla::whereMonth('fecha_estimada_entrega', $fechaReferencia->month)
                ->whereYear('fecha_estimada_entrega', $fechaReferencia->year)
                ->selectRaw('SUM(peso_total) as peso')
                ->first();

            $elementosSemana = Elemento::whereHas('planilla', fn($q) => $q->whereBetween('fecha_estimada_entrega', [$startOfWeek, $endOfWeek]))
                ->selectRaw('SUM(longitud * barras) as longitud_total, AVG(diametro) as diametro_avg')
                ->first();

            $elementosMes = Elemento::whereHas('planilla', fn($q) => $q->whereMonth('fecha_estimada_entrega', $fechaReferencia->month)
                ->whereYear('fecha_estimada_entrega', $fechaReferencia->year))
                ->selectRaw('SUM(longitud * barras) as longitud_total, AVG(diametro) as diametro_avg')
                ->first();

            $responseData = [
                'events' => $eventos->values(),
                'resources' => $resources,
                'totales' => [
                    'semana' => [
                        'peso' => $totalesSemana->peso ?? 0,
                        'longitud' => $elementosSemana->longitud_total ?? 0,
                        'diametro' => $elementosSemana->diametro_avg,
                    ],
                    'mes' => [
                        'peso' => $totalesMes->peso ?? 0,
                        'longitud' => $elementosMes->longitud_total ?? 0,
                        'diametro' => $elementosMes->diametro_avg,
                    ],
                ],
            ];

            // Guardar en caché
            cache()->put($cacheKey, $responseData, $cacheTTL);

            return response()->json($responseData);
        }

        // Para otros tipos (resources, events), generar sin caché
        $salidasEventos = $this->getEventosSalidas($startDate, $endDate, $viewType);
        $planillasEventos = $this->getEventosPlanillas($startDate, $endDate, $viewType);
        $resumenEventos = $this->getEventosResumen($planillasEventos['eventos'], $viewType);
        $eventos = collect()
            ->concat($planillasEventos['eventos'])
            ->concat($salidasEventos)
            ->concat($resumenEventos)
            ->concat(Festivo::eventosCalendario())
            ->sortBy([
                fn($e) => match ($e['tipo'] ?? $e['extendedProps']['tipo'] ?? '') {
                    'planilla' => 0,
                    'salida' => 1,
                    'resumen' => 2,
                    'festivo' => 3,
                    default => 99,
                },
                fn($e) => $e['extendedProps']['cod_obra'] ?? '',
            ])
            ->values();

        $resources = $this->getResources($eventos, $viewType);

        if ($tipo === 'resources') {
            return response()->json($resources);
        }
        if ($tipo === 'events') {
            return response()->json($eventos->values());
        }

        // Tipo no reconocido
        return response()->json(['error' => 'Tipo no válido'], 400);
    }
    /**
     * Obtiene el rango de fechas según la vista y los parámetros de la solicitud.
     */
    private function getDateRange(Request $request): array
    {

        $start = $request->input('start');
        $end = $request->input('end');
        $viewType = $request->input('viewType');

        // Vista día
        if (in_array($viewType, ['resourceTimeGridDay', 'timeGridDay', 'resourceTimelineDay']) && $start) {
            $fecha = $this->parseFlexibleDate($start)?->startOfDay() ?? now()->startOfDay();
            return [$fecha, $fecha->copy()->endOfDay()];
        }

        // Vista semana
        if (in_array($viewType, ['resourceTimeGridWeek', 'resourceTimelineWeek']) && $start && $end) {
            return [
                $this->parseFlexibleDate($start)?->startOfDay() ?? now()->startOfWeek(),
                $this->parseFlexibleDate($end)?->subSecond() ?? now()->endOfWeek()
            ];
        }

        // Vista mes
        if ($viewType === 'dayGridMonth' && $start && $end) {
            return [
                $this->parseFlexibleDate($start)?->startOfDay() ?? now()->startOfMonth(),
                $this->parseFlexibleDate($end)?->subSecond() ?? now()->endOfMonth()
            ];
        }

        // Por defecto
        return [
            $start ? ($this->parseFlexibleDate($start)?->startOfDay() ?? now()->startOfMonth()) : now()->startOfMonth(),
            $end ? ($this->parseFlexibleDate($end)?->subSecond() ?? now()->endOfMonth()) : now()->endOfMonth()
        ];
    }
    /**
     * Obtiene los totales de planillas y elementos para la semana y mes basados en la fecha de la vista.
     * NOTA: Este endpoint ya no se usa - los totales vienen con la respuesta de eventos (tipo=all)
     * Mantenido por compatibilidad y optimizado con consultas SQL directas.
     */
    public function getTotalesAjax(Request $request)
    {
        $fechaReferencia = $this->parseFlexibleDate($request->input('fecha')) ?? now();
        $startOfWeek = $fechaReferencia->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $fechaReferencia->copy()->endOfWeek(Carbon::SUNDAY);

        // 🚀 Optimizado: consultas SQL directas en lugar de cargar modelos completos
        $totalesSemana = Planilla::whereBetween('fecha_estimada_entrega', [$startOfWeek, $endOfWeek])
            ->selectRaw('SUM(peso_total) as peso')
            ->first();

        $totalesMes = Planilla::whereMonth('fecha_estimada_entrega', $fechaReferencia->month)
            ->whereYear('fecha_estimada_entrega', $fechaReferencia->year)
            ->selectRaw('SUM(peso_total) as peso')
            ->first();

        // Longitud y diámetro desde elementos
        $elementosSemana = Elemento::whereHas('planilla', fn($q) =>
            $q->whereBetween('fecha_estimada_entrega', [$startOfWeek, $endOfWeek])
        )->selectRaw('SUM(longitud * barras) as longitud_total, AVG(diametro) as diametro_avg')
         ->first();

        $elementosMes = Elemento::whereHas('planilla', fn($q) =>
            $q->whereMonth('fecha_estimada_entrega', $fechaReferencia->month)
             ->whereYear('fecha_estimada_entrega', $fechaReferencia->year)
        )->selectRaw('SUM(longitud * barras) as longitud_total, AVG(diametro) as diametro_avg')
         ->first();

        return response()->json([
            'semana' => [
                'peso' => $totalesSemana->peso ?? 0,
                'longitud' => $elementosSemana->longitud_total ?? 0,
                'diametro' => $elementosSemana->diametro_avg,
            ],
            'mes' => [
                'peso' => $totalesMes->peso ?? 0,
                'longitud' => $elementosMes->longitud_total ?? 0,
                'diametro' => $elementosMes->diametro_avg,
            ],
        ]);
    }

    /**
     * Busca planillas por código (contains) para mostrar en el filtro.
     */
    public function buscarPlanillas(Request $request)
    {
        $codigo = $request->input('codigo', '');

        if (strlen($codigo) < 2) {
            return response()->json([]);
        }

        $planillas = Planilla::where('codigo', 'LIKE', "%{$codigo}%")
            ->select('id', 'codigo', 'fecha_estimada_entrega')
            ->orderBy('fecha_estimada_entrega', 'asc')
            ->limit(20)
            ->get()
            ->map(function($p) {
                $fecha = 'S/F';
                $fechaISO = null;
                $fechaObj = null;

                if ($p->fecha_estimada_entrega) {
                    try {
                        if ($p->fecha_estimada_entrega instanceof Carbon) {
                            $fechaObj = $p->fecha_estimada_entrega;
                        } else {
                            $fechaStr = $p->fecha_estimada_entrega;

                            // Intentar formatos europeos comunes
                            foreach (['d/m/Y H:i', 'd/m/Y H:i:s', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d'] as $formato) {
                                try {
                                    $fechaObj = Carbon::createFromFormat($formato, $fechaStr);
                                    if ($fechaObj !== false) break;
                                } catch (\Exception $e) {
                                    continue;
                                }
                            }

                            if (!$fechaObj) {
                                $fechaObj = Carbon::parse($fechaStr);
                            }
                        }
                        $fecha = $fechaObj->format('d/m/Y');
                        $fechaISO = $fechaObj->format('Y-m-d');
                    } catch (\Exception $e) {
                        $fecha = 'S/F';
                    }
                }
                return [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'fecha' => $fecha,
                    'fechaISO' => $fechaISO,
                ];
            });

        return response()->json($planillas);
    }

    /**
     * Busca obras por código o nombre y devuelve sus planillas con fechas de entrega.
     * Filtra desde hace 2 semanas hasta fin de año.
     */
    public function buscarObras(Request $request)
    {
        $codigo = $request->input('codigo', '');
        $nombre = $request->input('nombre', '');

        if (strlen($codigo) < 2 && strlen($nombre) < 2) {
            return response()->json([]);
        }

        // Rango de fechas: desde hace 2 semanas hasta fin de año
        $fechaInicio = Carbon::now()->subWeeks(2)->startOfDay();
        $fechaFin = Carbon::now()->endOfYear();

        $query = Obra::query();

        if (strlen($codigo) >= 2) {
            $query->where('cod_obra', 'LIKE', "%{$codigo}%");
        }
        if (strlen($nombre) >= 2) {
            $query->where('obra', 'LIKE', "%{$nombre}%");
        }

        $obras = $query->with(['planillas' => function($q) use ($fechaInicio, $fechaFin) {
                $q->whereNotNull('fecha_estimada_entrega')
                  ->whereBetween('fecha_estimada_entrega', [$fechaInicio, $fechaFin])
                  ->select('id', 'codigo', 'obra_id', 'fecha_estimada_entrega')
                  ->orderBy('fecha_estimada_entrega', 'asc');
            }])
            ->select('id', 'cod_obra', 'obra')
            ->limit(10)
            ->get()
            ->filter(fn($obra) => $obra->planillas->count() > 0)
            ->map(function($obra) {
                $planillasData = $obra->planillas->map(function($p) {
                    $fechaObj = null;
                    $fecha = 'S/F';
                    $fechaISO = null;

                    if ($p->fecha_estimada_entrega) {
                        try {
                            if ($p->fecha_estimada_entrega instanceof Carbon) {
                                $fechaObj = $p->fecha_estimada_entrega;
                            } else {
                                foreach (['d/m/Y H:i', 'd/m/Y H:i:s', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d'] as $formato) {
                                    try {
                                        $fechaObj = Carbon::createFromFormat($formato, $p->fecha_estimada_entrega);
                                        if ($fechaObj !== false) break;
                                    } catch (\Exception $e) {
                                        continue;
                                    }
                                }
                                if (!$fechaObj) {
                                    $fechaObj = Carbon::parse($p->fecha_estimada_entrega);
                                }
                            }
                            $fecha = $fechaObj->format('d/m/Y');
                            $fechaISO = $fechaObj->format('Y-m-d');
                        } catch (\Exception $e) {
                            $fecha = 'S/F';
                        }
                    }

                    return [
                        'id' => $p->id,
                        'codigo' => $p->codigo,
                        'fecha' => $fecha,
                        'fechaISO' => $fechaISO,
                    ];
                });

                // Última fecha de entrega (la más reciente del futuro o la última en general)
                $ultimaFechaISO = $planillasData->pluck('fechaISO')->filter()->last();

                return [
                    'id' => $obra->id,
                    'codigo' => $obra->cod_obra,
                    'nombre' => $obra->obra,
                    'planillas' => $planillasData->values(),
                    'ultimaFechaISO' => $ultimaFechaISO,
                ];
            })->values();

        return response()->json($obras);
    }

    /**
     * Busca clientes por código o nombre y devuelve sus obras con fechas de entrega.
     * Filtra desde hace 2 semanas hasta fin de año.
     */
    public function buscarClientes(Request $request)
    {
        $codigo = $request->input('codigo', '');
        $nombre = $request->input('nombre', '');

        if (strlen($codigo) < 2 && strlen($nombre) < 2) {
            return response()->json([]);
        }

        // Rango de fechas: desde hace 2 semanas hasta fin de año
        $fechaInicio = Carbon::now()->subWeeks(2)->startOfDay();
        $fechaFin = Carbon::now()->endOfYear();

        $query = Cliente::query();

        if (strlen($codigo) >= 2) {
            $query->where('codigo', 'LIKE', "%{$codigo}%");
        }
        if (strlen($nombre) >= 2) {
            $query->where('empresa', 'LIKE', "%{$nombre}%");
        }

        $clientes = $query->with(['obras' => function($q) use ($fechaInicio, $fechaFin) {
                $q->whereHas('planillas', function($pq) use ($fechaInicio, $fechaFin) {
                    $pq->whereNotNull('fecha_estimada_entrega')
                       ->whereBetween('fecha_estimada_entrega', [$fechaInicio, $fechaFin]);
                })
                ->with(['planillas' => function($pq) use ($fechaInicio, $fechaFin) {
                    $pq->whereNotNull('fecha_estimada_entrega')
                       ->whereBetween('fecha_estimada_entrega', [$fechaInicio, $fechaFin])
                       ->select('id', 'codigo', 'obra_id', 'fecha_estimada_entrega')
                       ->orderBy('fecha_estimada_entrega', 'asc');
                }])
                ->select('id', 'cod_obra', 'obra', 'cliente_id');
            }])
            ->select('id', 'codigo', 'empresa')
            ->limit(10)
            ->get()
            ->filter(fn($cliente) => $cliente->obras->count() > 0)
            ->map(function($cliente) {
                // Recopilar todas las planillas con sus fechas
                $todasPlanillas = collect();

                foreach ($cliente->obras as $obra) {
                    foreach ($obra->planillas as $p) {
                        if ($p->fecha_estimada_entrega) {
                            try {
                                $fechaObj = $p->fecha_estimada_entrega instanceof Carbon
                                    ? $p->fecha_estimada_entrega
                                    : Carbon::parse($p->fecha_estimada_entrega);

                                $todasPlanillas->push([
                                    'planillaCodigo' => $p->codigo,
                                    'fecha' => $fechaObj->format('d/m/Y'),
                                    'fechaISO' => $fechaObj->format('Y-m-d'),
                                    'obraCodigo' => $obra->cod_obra,
                                    'obraNombre' => $obra->obra,
                                ]);
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    }
                }

                // Ordenar por fecha ascendente
                $todasPlanillas = $todasPlanillas->sortBy('fechaISO')->values();

                return [
                    'id' => $cliente->id,
                    'codigo' => $cliente->codigo,
                    'nombre' => $cliente->empresa,
                    'obrasCount' => $cliente->obras->count(),
                    'planillas' => $todasPlanillas,
                ];
            })->values();

        return response()->json($clientes);
    }

    /**
     * Obtiene los eventos de resumen basados en los eventos de planillas generados.
     * @param \Illuminate\Support\Collection $eventosPlanillas - Eventos ya generados (con fechas de salida aplicadas)
     * @param string $viewType
     * @return \Illuminate\Support\Collection
     */
    private function getEventosResumen($eventosPlanillas, string $viewType)
    {
        // ------------------- RESUMEN POR DÍA -------------------
        // Agrupar eventos por fecha (extraída del campo 'start')
        $resumenPorDia = $eventosPlanillas
            ->groupBy(fn($evento) => Carbon::parse($evento['start'])->toDateString())
            ->map(fn($grupo, $fechaDia) => [
                'fecha' => Carbon::parse($fechaDia),
                'pesoTotal' => $grupo->sum(fn($e) => $e['extendedProps']['pesoTotal'] ?? 0),
                'longitudTotal' => $grupo->sum(fn($e) => $e['extendedProps']['longitudTotal'] ?? 0),
                'diametroMedio' => $grupo->avg(fn($e) => $e['extendedProps']['diametroMedio'] ?? 0),
            ])->values();

        $resumenEventosDia = $resumenPorDia->map(function ($r) use ($viewType) {
            $titulo = "📊 Resumen del día";

            // Usar formato de fecha consistente sin conversión de zona horaria
            $fechaStr = $r['fecha']->format('Y-m-d');

            $evento = [
                'title' => $titulo,
                'start' => $fechaStr,
                'backgroundColor' => '#fef3c7',
                'borderColor' => '#fbbf24',
                'textColor' => '#92400e',
                'classNames' => ['evento-resumen-diario'],
                'extendedProps' => [
                    'tipo' => 'resumen-dia',
                    'pesoTotal' => $r['pesoTotal'],
                    'longitudTotal' => $r['longitudTotal'],
                    'diametroMedio' => $r['diametroMedio'],
                    'fecha' => $fechaStr,
                ],
            ];

            // Configuración según vista
            if ($viewType === 'dayGridMonth') {
                // Vista mensual: evento normal visible
                $evento['allDay'] = true;
                $evento['display'] = 'auto';
            } elseif ($viewType === 'resourceTimelineWeek') {
                // Vista semanal: evento normal visible con resourceId para todas las obras
                $evento['display'] = 'auto';
                $evento['resourceId'] = '_resumen_'; // ID especial para que aparezca en todas las filas
            } else {
                // Vista diaria: evento background (datos disponibles pero no visible como evento)
                $evento['allDay'] = true;
                $evento['display'] = 'background';
            }

            return $evento;
        });

        // ------------------- SELECCIÓN SEGÚN VISTA -------------------
        $eventosResumen = collect();


        $eventosResumen = $eventosResumen->merge($resumenEventosDia);


        return $eventosResumen;
    }

    /**
     * Calcula los resúmenes diarios para fechas específicas.
     * Útil para actualizar resúmenes después de mover eventos.
     *
     * @param array $fechas Array de fechas en formato Y-m-d
     * @return array Resúmenes indexados por fecha
     */
    private function calcularResumenesDias(array $fechas): array
    {
        $resumenes = [];

        foreach ($fechas as $fechaStr) {
            // Obtener peso de planillas con fecha_estimada_entrega en ese día
            $datosPlanillas = Planilla::whereDate('fecha_estimada_entrega', $fechaStr)
                ->selectRaw('COALESCE(SUM(peso_total), 0) as peso_total')
                ->first();

            // Obtener datos de elementos de esas planillas (longitud * barras = longitud total)
            $datosElementos = Elemento::whereHas('planilla', function ($q) use ($fechaStr) {
                    $q->whereDate('fecha_estimada_entrega', $fechaStr);
                })
                ->selectRaw('COALESCE(SUM(longitud * barras), 0) as longitud_total, AVG(diametro) as diametro_medio')
                ->first();

            // También considerar elementos con fecha_entrega propia
            $datosElementosPropios = Elemento::whereDate('fecha_entrega', $fechaStr)
                ->selectRaw('COALESCE(SUM(peso), 0) as peso_total, COALESCE(SUM(longitud * barras), 0) as longitud_total, AVG(diametro) as diametro_medio')
                ->first();

            $pesoTotal = ($datosPlanillas->peso_total ?? 0) + ($datosElementosPropios->peso_total ?? 0);
            $longitudTotal = ($datosElementos->longitud_total ?? 0) + ($datosElementosPropios->longitud_total ?? 0);

            // Calcular diámetro medio ponderado si hay datos
            $diametroMedio = 0;
            $countDiametros = 0;
            if ($datosElementos->diametro_medio) {
                $diametroMedio += $datosElementos->diametro_medio;
                $countDiametros++;
            }
            if ($datosElementosPropios->diametro_medio) {
                $diametroMedio += $datosElementosPropios->diametro_medio;
                $countDiametros++;
            }
            if ($countDiametros > 0) {
                $diametroMedio = $diametroMedio / $countDiametros;
            }

            $resumenes[$fechaStr] = [
                'fecha' => $fechaStr,
                'pesoTotal' => round($pesoTotal, 2),
                'longitudTotal' => round($longitudTotal, 2),
                'diametroMedio' => round($diametroMedio, 2),
            ];
        }

        return $resumenes;
    }

    private function getEventosSalidas(Carbon $startDate, Carbon $endDate, string $viewType = '')
    {
        // 🚀 Optimizado: solo cargar campos necesarios de cada relación
        $salidas = Salida::with([
            'salidaClientes:id,salida_id,obra_id,cliente_id',
            'salidaClientes.obra:id,obra,cod_obra,cliente_id',
            'salidaClientes.obra.cliente:id,empresa,codigo',
            'salidaClientes.cliente:id,empresa,codigo',
            'paquetes:id,planilla_id,peso',
            'paquetes.planilla:id,obra_id',
            'paquetes.planilla.obra:id,obra,cod_obra,cliente_id',
            'paquetes.planilla.obra.cliente:id,empresa,codigo',
            'paquetes.etiquetas:id,paquete_id,peso',
            'empresaTransporte:id,nombre',
            'camion:id,modelo',
        ])
            ->whereBetween('fecha_salida', [$startDate, $endDate])
            ->get();

        return $salidas->map(function ($salida) use ($viewType) {
            $empresa = optional($salida->empresaTransporte)->nombre;
            $camion = optional($salida->camion)->modelo;
            // Calcular peso real de los paquetes (desde paquete.peso o suma de etiquetas)
            $pesoTotal = round($salida->paquetes->sum(function ($paquete) {
                if ($paquete->peso && $paquete->peso > 0) {
                    return $paquete->peso;
                }
                return $paquete->etiquetas->sum('peso') ?? 0;
            }), 0);
            $fechaInicio = Carbon::parse($salida->fecha_salida);

            // En vista mensual, hacer eventos de día completo para que no se extiendan
            $isMonthView = $viewType === 'dayGridMonth';
            $fechaFin = $isMonthView ? null : $fechaInicio->copy()->addHours(3);

            $color = $salida->estado === 'completada' ? '#4CAF50' : '#3B82F6';
            $codigoSage = $salida->codigo_sage ? " - {$salida->codigo_sage}" : '';

            // Recopilar todas las obras y clientes únicos de esta salida
            $obrasClientes = collect();

            // 1. Desde salidaClientes (relación directa)
            if ($salida->salidaClientes->isNotEmpty()) {
                foreach ($salida->salidaClientes as $relacion) {
                    // Priorizar cliente de la relación, sino el de la obra
                    $clienteNombre = $relacion->cliente?->empresa
                        ?? $relacion->obra?->cliente?->empresa
                        ?? 'Cliente desconocido';
                    $clienteId = $relacion->cliente_id
                        ?? $relacion->obra?->cliente_id;
                    $codCliente = $relacion->cliente?->codigo
                        ?? $relacion->obra?->cliente?->codigo
                        ?? '';

                    $obrasClientes->push([
                        'obra_id' => $relacion->obra_id,
                        'obra' => $relacion->obra?->obra ?? 'Obra desconocida',
                        'cod_obra' => $relacion->obra?->cod_obra ?? '',
                        'cliente' => $clienteNombre,
                        'cliente_id' => $clienteId,
                        'cod_cliente' => $codCliente,
                    ]);
                }
            }

            // 2. Desde paquetes (pueden tener obras diferentes)
            if ($salida->paquetes->isNotEmpty()) {
                foreach ($salida->paquetes as $paquete) {
                    if ($paquete->planilla?->obra) {
                        $obra = $paquete->planilla->obra;
                        // Solo agregar si no existe ya
                        if (!$obrasClientes->where('obra_id', $obra->id)->count()) {
                            $obrasClientes->push([
                                'obra_id' => $obra->id,
                                'obra' => $obra->obra,
                                'cod_obra' => $obra->cod_obra ?? '',
                                'cliente' => $obra->cliente?->empresa ?? 'Cliente desconocido',
                                'cliente_id' => $obra->cliente?->id,
                                'cod_cliente' => $obra->cliente?->codigo ?? '',
                            ]);
                        }
                    }
                }
            }

            // Eliminar duplicados por obra_id
            $obrasClientes = $obrasClientes->unique('obra_id')->values();

            // Determinar si es vista diaria
            $isDayView = in_array($viewType, ['resourceTimeGridDay', 'timeGridDay', 'resourceTimelineDay']);

            // Construir el título según la vista
            if ($isDayView) {
                // Vista diaria: título completo con obras, clientes y empresa de transporte
                $obrasTexto = $obrasClientes->pluck('cod_obra')->filter()->implode(', ') ?: 'Sin obra';
                $clientesTexto = $obrasClientes->pluck('cliente')->unique()->filter()->implode(', ') ?: 'Sin cliente';
                $empresaTexto = $empresa ?: 'Sin transporte';

                $titulo = "{$salida->codigo_salida} | {$pesoTotal} kg\n";
                $titulo .= "📍 {$obrasTexto}\n";
                $titulo .= "👤 {$clientesTexto}\n";
                $titulo .= "🚚 {$empresaTexto}";
            } else {
                // Otras vistas: título compacto
                $titulo = "{$salida->codigo_salida} - {$pesoTotal} kg";
            }

            // Determinar resourceId (usar la primera obra, o _sin_obra_)
            $resourceId = '_sin_obra_';
            if ($obrasClientes->isNotEmpty()) {
                $resourceId = (string) $obrasClientes->first()['obra_id'];
            }
            if ($isDayView) {
                $hora = $fechaInicio->hour;
                // Si la hora está fuera del rango visible, ajustar a las 8:00
                if ($hora < 6 || $hora >= 18) {
                    $fechaInicio = $fechaInicio->copy()->setTime(8, 0, 0);
                }
                $fechaFin = $fechaInicio->copy()->addHours(2);
            }

            $evento = [
                'title' => $titulo,
                'id' => (string) $salida->id,
                'start' => $isMonthView ? $fechaInicio->toDateString() : $fechaInicio->toIso8601String(),
                'tipo' => 'salida',
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'tipo' => 'salida',
                    'salida_id' => $salida->id,
                    'codigo_salida' => $salida->codigo_salida,
                    'codigo_sage' => $salida->codigo_sage,
                    'obras' => $obrasClientes->map(fn($oc) => [
                        'id' => $oc['obra_id'],
                        'nombre' => $oc['obra'],
                        'codigo' => $oc['cod_obra'],
                        'cliente' => $oc['cliente'] ?? '',
                        'cod_cliente' => $oc['cod_cliente'] ?? '',
                    ])->toArray(),
                    'clientes' => $obrasClientes->map(fn($oc) => [
                        'id' => $oc['cliente_id'],
                        'nombre' => $oc['cliente'],
                        'codigo' => $oc['cod_cliente'] ?? '',
                    ])->unique('id')->filter(fn($c) => $c['nombre'])->values()->toArray(),
                    'empresa' => $empresa,
                    'empresa_id' => $salida->empresa_id,
                    'camion' => $camion,
                    'comentario' => $salida->comentario ?? '',
                    'peso_total' => $pesoTotal,
                ],
            ];

            // Solo agregar 'end' si no es vista mensual
            if (!$isMonthView && $fechaFin) {
                $evento['end'] = $fechaFin->toIso8601String();
            } else if ($isMonthView) {
                // En vista mensual, marcar como evento de día completo
                $evento['allDay'] = true;
            }

            // Agregar resourceId para todas las vistas de recursos
            $evento['resourceId'] = $resourceId;

            return $evento;
        });
    }

    /**
     * Obtiene los eventos de planillas agrupados por obra, día y salida.
     * Si hay salidas asociadas, crea un evento por cada salida.
     * Si no hay salidas, crea un solo evento agrupado.
     *
     * Los elementos con fecha_entrega propia se agrupan por su fecha.
     * Los elementos sin fecha_entrega usan la fecha_estimada_entrega de la planilla.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getEventosPlanillas(Carbon $startDate, Carbon $endDate, string $viewType = ''): array
    {
        // 🚀 Optimizado: solo cargar campos necesarios de cada relación
        $planillas = Planilla::with([
                'obra:id,obra,cod_obra,cliente_id',
                'obra.cliente:id,empresa,codigo',
                'elementos:id,planilla_id,peso,fecha_entrega,longitud,barras,diametro',
                'paquetes:id,planilla_id,peso',
                'paquetes.salidas:id,codigo_salida,fecha_salida',
                'paquetes.etiquetas:id,paquete_id,peso'
            ])
            ->whereBetween('fecha_estimada_entrega', [$startDate, $endDate])
            ->get();

        // 🔹 También buscar elementos con fecha_entrega propia en el rango (cuya planilla puede estar fuera)
        $elementosConFechaPropia = Elemento::with([
                'planilla:id,obra_id,codigo,fecha_estimada_entrega',
                'planilla.obra:id,obra,cod_obra,cliente_id',
                'planilla.obra.cliente:id,empresa,codigo'
            ])
            ->whereBetween('fecha_entrega', [$startDate, $endDate])
            ->whereHas('planilla', function ($q) use ($startDate, $endDate) {
                // Excluir los que ya están en planillas del rango principal
                $q->where(function ($sub) use ($startDate, $endDate) {
                    $sub->where('fecha_estimada_entrega', '<', $startDate)
                        ->orWhere('fecha_estimada_entrega', '>', $endDate);
                });
            })
            ->get(['id', 'planilla_id', 'peso', 'fecha_entrega', 'longitud', 'barras', 'diametro']);

        // Determinar si es vista diaria
        $isDayView = in_array($viewType, ['resourceTimeGridDay', 'timeGridDay', 'resourceTimelineDay']);

        $eventosFinales = collect();

        // 🔹 Agrupar planillas por obra y fecha_estimada_entrega (comportamiento original)
        $grupos = $planillas->groupBy(function ($p) {
            return $p->obra_id . '|' . Carbon::parse($p->getRawOriginal('fecha_estimada_entrega'))->toDateString();
        });

        foreach ($grupos as $key => $grupo) {
            $obraId = $grupo->first()->obra_id;
            $obra = $grupo->first()->obra;
            $nombreObra = $obra?->obra ?? 'Obra desconocida';
            $codObra = $obra?->cod_obra ?? 'Código desconocido';
            $clienteNombre = $obra?->cliente?->empresa ?? 'Sin cliente';
            $codCliente = $obra?->cliente?->codigo ?? '';
            $fechaBase = Carbon::parse($grupo->first()->getRawOriginal('fecha_estimada_entrega'));

            $planillasIds = $grupo->pluck('id')->toArray();
            $planillasCodigos = $grupo->pluck('codigo')->filter()->toArray();

            // Calcular peso de elementos SIN fecha_entrega propia (usan fecha de planilla)
            $pesoElementosSinFecha = $grupo->sum(function ($p) {
                return $p->elementos->whereNull('fecha_entrega')->sum('peso') ?? 0;
            });

            // IDs de elementos sin fecha propia
            $elementosIdsSinFecha = $grupo->flatMap(function ($p) {
                return $p->elementos->whereNull('fecha_entrega')->pluck('id');
            })->toArray();

            // Solo crear evento si hay elementos sin fecha propia
            if ($pesoElementosSinFecha > 0) {
                // Buscar paquetes y salidas
                $paquetesPorSalida = collect();

                foreach ($grupo as $planilla) {
                    foreach ($planilla->paquetes as $paquete) {
                        if ($paquete->salidas->isNotEmpty()) {
                            foreach ($paquete->salidas as $salida) {
                                if (!$paquetesPorSalida->has($salida->id)) {
                                    $paquetesPorSalida[$salida->id] = [
                                        'salida' => $salida,
                                        'paquetes' => collect(),
                                        'planillas' => collect(),
                                    ];
                                }
                                if (!$paquetesPorSalida[$salida->id]['paquetes']->contains('id', $paquete->id)) {
                                    $paquetesPorSalida[$salida->id]['paquetes']->push($paquete);
                                }
                                if (!$paquetesPorSalida[$salida->id]['planillas']->contains('id', $planilla->id)) {
                                    $paquetesPorSalida[$salida->id]['planillas']->push($planilla);
                                }
                            }
                        }
                    }
                }

                // Crear eventos por salida
                foreach ($paquetesPorSalida as $salidaId => $data) {
                    $salida = $data['salida'];
                    $paquetesDelEvento = $data['paquetes'];
                    $planillasDelEvento = $data['planillas'];

                    $pesoPaquetesSalida = $paquetesDelEvento->sum(function ($paquete) {
                        return $paquete->peso > 0 ? $paquete->peso : ($paquete->etiquetas->sum('peso') ?? 0);
                    });

                    $fechaSalida = Carbon::parse($salida->fecha_salida);

                    $evento = $this->crearEventoPlanillasConPeso(
                        $planillasDelEvento,
                        $obraId,
                        $codObra,
                        $nombreObra,
                        $clienteNombre,
                        $codCliente,
                        $fechaSalida,
                        $isDayView,
                        $salida,
                        $pesoElementosSinFecha,
                        $pesoPaquetesSalida,
                        $elementosIdsSinFecha
                    );
                    $eventosFinales->push($evento);
                }

                // Evento para los que no tienen salida
                if ($paquetesPorSalida->isEmpty()) {
                    $evento = $this->crearEventoPlanillasConPeso(
                        $grupo,
                        $obraId,
                        $codObra,
                        $nombreObra,
                        $clienteNombre,
                        $codCliente,
                        $fechaBase,
                        $isDayView,
                        null,
                        $pesoElementosSinFecha,
                        0,
                        $elementosIdsSinFecha
                    );
                    $eventosFinales->push($evento);
                }
            }

            // 🔹 Crear eventos separados para elementos CON fecha_entrega propia
            $elementosConFechaPropiaDelGrupo = $grupo->flatMap(function ($p) use ($startDate, $endDate) {
                return $p->elementos->filter(function ($e) use ($startDate, $endDate) {
                    return $e->fecha_entrega &&
                        $e->fecha_entrega->gte($startDate) &&
                        $e->fecha_entrega->lte($endDate);
                });
            });

            // Agrupar por fecha_entrega
            $elementosPorFecha = $elementosConFechaPropiaDelGrupo->groupBy(fn($e) => $e->fecha_entrega->toDateString());

            foreach ($elementosPorFecha as $fecha => $elementos) {
                $pesoFecha = $elementos->sum('peso');
                $elementosIdsFecha = $elementos->pluck('id')->toArray();
                $planillasDeElementos = $grupo->filter(fn($p) => $elementos->pluck('planilla_id')->contains($p->id));

                $evento = $this->crearEventoPlanillasConPeso(
                    $planillasDeElementos,
                    $obraId,
                    $codObra,
                    $nombreObra,
                    $clienteNombre,
                    $codCliente,
                    Carbon::parse($fecha),
                    $isDayView,
                    null,
                    $pesoFecha,
                    0,
                    $elementosIdsFecha
                );
                $eventosFinales->push($evento);
            }
        }

        // 🔹 Procesar elementos con fecha propia cuyas planillas están fuera del rango
        $elementosExternosPorObraYFecha = $elementosConFechaPropia->groupBy(function ($e) {
            return $e->planilla->obra_id . '|' . $e->fecha_entrega->toDateString();
        });

        foreach ($elementosExternosPorObraYFecha as $key => $elementos) {
            $primerElemento = $elementos->first();
            $planilla = $primerElemento->planilla;
            $obra = $planilla->obra;

            $evento = $this->crearEventoPlanillasConPeso(
                collect([$planilla]),
                $planilla->obra_id,
                $obra?->cod_obra ?? 'Código desconocido',
                $obra?->obra ?? 'Obra desconocida',
                $obra?->cliente?->empresa ?? 'Sin cliente',
                $obra?->cliente?->codigo ?? '',
                $primerElemento->fecha_entrega,
                $isDayView,
                null,
                $elementos->sum('peso'),
                0,
                $elementos->pluck('id')->toArray()
            );
            $eventosFinales->push($evento);
        }

        // Ordenar eventos
        $eventosOrdenados = $eventosFinales->sortBy([
            fn($e) => (int) preg_replace('/\D/', '', $e['extendedProps']['cod_obra'] ?? '0'),
            fn($e) => $e['start'],
        ])->values();

        return [
            'planillas' => $planillas,
            'eventos' => $eventosOrdenados,
        ];
    }

    /**
     * Crea un evento de planillas para el calendario.
     *
     * @param Collection $planillas - Planillas asociadas (para referencia)
     * @param int $obraId
     * @param string $codObra
     * @param string $nombreObra
     * @param string $clienteNombre
     * @param string $codCliente
     * @param Carbon $fechaBase
     * @param bool $isDayView
     * @param Salida|null $salida
     * @param float $pesoPlanillas - Peso total de las planillas/elementos
     * @param float $pesoPaquetesSalida - Peso de los paquetes de la salida
     * @param array $elementosIds - IDs de elementos incluidos en este evento
     */
    private function crearEventoPlanillasConPeso(
        $planillas,
        $obraId,
        $codObra,
        $nombreObra,
        $clienteNombre,
        $codCliente,
        Carbon $fechaBase,
        bool $isDayView,
        ?Salida $salida = null,
        float $pesoPlanillas = 0,
        float $pesoPaquetesSalida = 0,
        array $elementosIds = []
    ): array {
        // Usar la hora más temprana de las planillas del grupo (o 07:00 por defecto)
        $horaMinima = $planillas
            ->map(fn($p) => $p->getRawOriginal('fecha_estimada_entrega') ? Carbon::parse($p->getRawOriginal('fecha_estimada_entrega'))->format('H:i') : '07:00')
            ->filter(fn($h) => $h !== '00:00')
            ->sort()
            ->first() ?? '07:00';

        [$hora, $minuto] = explode(':', $horaMinima);
        $fechaInicio = $fechaBase->copy()->setTime((int)$hora, (int)$minuto, 0);
        $planillasIds = $planillas->pluck('id')->toArray();
        $planillasCodigos = $planillas->pluck('codigo')->filter()->toArray();

        // 👉 Diámetro medio
        $diametros = $planillas->flatMap->elementos->pluck('diametro')->filter();
        $diametroMedio = $diametros->isNotEmpty()
            ? number_format($diametros->avg(), 2, '.', '')
            : null;

        // 👉 Color según estado
        $alMenosUnaFabricando = $planillas->contains(fn($p) => $p->estado === 'fabricando');
        $todasCompletadas = $planillas->every(fn($p) => $p->estado === 'completada');

        if ($todasCompletadas) {
            $color = '#22c55e'; // verde
        } elseif ($alMenosUnaFabricando) {
            $color = '#facc15'; // amarillo
        } else {
            $color = '#9CA3AF'; // gris
        }

        // Construir título según la vista
        $salidaCodigo = $salida ? $salida->codigo_salida : null;

        if ($isDayView) {
            $titulo = "{$codObra} - {$nombreObra}\n";
            $titulo .= "👤 {$clienteNombre}\n";
            $titulo .= "📦 " . number_format($pesoPlanillas, 0) . " kg";
        } else {
            $titulo = $codObra . ' - ' . $nombreObra . " - " . number_format($pesoPlanillas, 0) . " kg";
        }

        // ID único del evento
        $eventoId = 'planillas-' . $obraId . '-' . $fechaBase->format('Y-m-d');
        if ($salida) {
            $eventoId .= '-salida-' . $salida->id;
        } else {
            $eventoId .= '-sin-salida';
        }

        // 🚀 Optimizado: solo enviar campos que se usan en el frontend
        return [
            'title' => $titulo,
            'id' => $eventoId,
            'start' => $fechaInicio->toIso8601String(),
            'end' => $fechaInicio->copy()->addHours(2)->toIso8601String(),
            'backgroundColor' => $color,
            'borderColor' => $color,
            'tipo' => 'planilla',
            'resourceId' => (string) $obraId,
            'extendedProps' => [
                'tipo' => 'planilla',
                'obra_id' => $obraId,
                'cod_obra' => $codObra,
                'nombre_obra' => $nombreObra,
                'cliente' => $clienteNombre,
                'cod_cliente' => $codCliente,
                'pesoTotal' => $pesoPlanillas,
                'longitudTotal' => $planillas->flatMap->elementos->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0)),
                'planillas_ids' => $planillasIds,
                'planillas_codigos' => $planillasCodigos,
                'elementos_ids' => $elementosIds,
                'diametroMedio' => $diametroMedio,
                'tieneSalidas' => $salida !== null,
                'salida_id' => $salida?->id,
                'salida_codigo' => $salidaCodigo,
                'hora_entrega' => $horaMinima,
            ],
        ];
    }

    private function getResources($eventos, $viewType = '')
    {
        $resourceIds = $eventos->pluck('resourceId')->filter()->unique()->values();
        $obras = Obra::with('cliente')->whereIn('id', $resourceIds)->orderBy('obra')->get();

        $resources = collect();

        // Agregar recurso especial para resúmenes en vista semanal
        if ($viewType === 'resourceTimelineWeek') {
            $resources->push([
                'id' => '_resumen_',
                'title' => '📊 Resumen Diario',
                'cliente' => '',
                'cod_obra' => '',
                'orderIndex' => 0, // Aparece primero
            ]);
        }

        // Agregar recurso especial para salidas sin obra asignada
        $tieneSalidasSinObra = $eventos->contains(function ($evento) {
            return isset($evento['resourceId']) && $evento['resourceId'] === '_sin_obra_';
        });

        if ($tieneSalidasSinObra) {
            $resources->push([
                'id' => '_sin_obra_',
                'title' => '🚚 Salidas sin Obra',
                'cliente' => '',
                'cod_obra' => '',
                'orderIndex' => 0.5, // Aparece después del resumen pero antes de las obras
            ]);
        }

        // Agregar recursos de obras
        $obrasResources = $obras->map(fn($obra) => [
            'id' => (string) $obra->id,
            'title' => $obra->obra,
            'cliente' => optional($obra->cliente)->empresa,
            'cod_obra' => $obra->cod_obra,
            'orderIndex' => 1,
        ]);

        $resources = $resources->concat($obrasResources);

        // Para vista diaria, si no hay recursos, agregar un recurso por defecto
        $isDayView = in_array($viewType, ['resourceTimeGridDay', 'timeGridDay', 'resourceTimelineDay']);
        if ($isDayView && $resources->isEmpty()) {
            $resources->push([
                'id' => '_default_',
                'title' => 'Sin obras programadas',
                'cliente' => '',
                'cod_obra' => '',
                'orderIndex' => 1,
            ]);
        }

        return $resources->values();
    }

    public function guardarComentario(Request $request, $id, ActionLoggerService $logger)
    {
        $request->validate([
            'comentario' => 'nullable|string|max:1000'
        ]);

        $salida = Salida::findOrFail($id);
        $comentarioAnterior = $salida->comentario;
        $salida->comentario = $request->comentario;
        $salida->save();

        $logger->logPlanificacion('comentario_guardado', [
            'salida_codigo' => $salida->codigo ?? 'N/A',
            'codigo_sage' => $salida->codigo_sage ?? 'N/A',
            'fecha_salida' => $salida->fecha_salida ? Carbon::parse($salida->fecha_salida)->format('Y-m-d H:i') : 'N/A',
            'tenia_comentario' => !empty($comentarioAnterior),
            'tiene_comentario' => !empty($salida->comentario),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comentario guardado correctamente',
            'comentario' => $salida->comentario,
            'salida_id' => $salida->id
        ]);
    }

    public function update(Request $request, $id, ActionLoggerService $logger)
    {
        $request->validate([
            'fecha' => 'required|date',
            'tipo' => 'required|in:salida,planilla',
        ]);

        $fecha = $this->parseFlexibleDate($request->fecha)?->timezone('Europe/Madrid') ?? now()->timezone('Europe/Madrid');
        $verificarFabricacion = true; // Siempre verificar fabricación

        if ($request->tipo === 'salida') {
            Log::info('🛠 Actualizando salida', [
                'id' => $id,
                'nueva_fecha_salida' => $fecha->toDateTimeString()
            ]);

            $salida = Salida::findOrFail($id);
            $fechaAnterior = $salida->fecha_salida;
            $salida->fecha_salida = $fecha;
            $salida->save();

            // Registrar en historial de planificación salidas
            \App\Models\LogPlanificacionSalidas::registrar(
                'mover_salida',
                "ha movido la salida {$salida->codigo_sage} de " . ($fechaAnterior ? Carbon::parse($fechaAnterior)->format('d/m/Y') : 'S/F') . " a " . $fecha->format('d/m/Y'),
                [
                    'salida_id' => $salida->id,
                    'codigo_sage' => $salida->codigo_sage,
                    'fecha_anterior' => $fechaAnterior ? Carbon::parse($fechaAnterior)->format('Y-m-d') : null,
                    'fecha_nueva' => $fecha->format('Y-m-d'),
                ],
                ['salida_id' => $salida->id],
                [
                    'salida_id' => $salida->id,
                    'fecha_anterior' => $fechaAnterior ? Carbon::parse($fechaAnterior)->format('Y-m-d H:i:s') : null,
                ]
            );

            $logger->logPlanificacion('fecha_salida_actualizada', [
                'salida_codigo' => $salida->codigo ?? 'N/A',
                'codigo_sage' => $salida->codigo_sage ?? 'N/A',
                'fecha_anterior' => $fechaAnterior ? Carbon::parse($fechaAnterior)->format('Y-m-d H:i') : 'N/A',
                'fecha_nueva' => $fecha->format('Y-m-d H:i'),
            ]);

            // ✅ Solo enviar alerta si la salida tiene código SAGE
            if (!is_null($salida->codigo_sage)) {
                $alertaService = app(AlertaService::class);
                $emisorId = auth()->id();

                $usuariosAdmin = User::whereHas('departamentos', function ($q) {
                    $q->where('nombre', 'Administración');
                })->get();

                foreach ($usuariosAdmin as $usuario) {
                    $alertaService->crearAlerta(
                        emisorId: $emisorId,
                        destinatarioId: $usuario->id,
                        mensaje: 'Se ha actualizado una salida (' . $salida->codigo_sage . ')',
                        tipo: 'cambios en salida',
                    );
                }
            } else {
                Log::info('📭 No se envió alerta: salida sin código SAGE');
            }

            // Calcular resúmenes de los días afectados (origen y destino)
            $fechasAfectadas = array_unique(array_filter([
                $fechaAnterior ? Carbon::parse($fechaAnterior)->format('Y-m-d') : null,
                $fecha->format('Y-m-d'),
            ]));
            $resumenesDias = $this->calcularResumenesDias($fechasAfectadas);

            return response()->json([
                'success' => true,
                'modelo' => 'salida',
                'resumenes_dias' => $resumenesDias,
            ]);
        }


        if ($request->tipo === 'planilla') {
            Log::info('🛠 Actualizando planillas', [
                'planillas_ids' => $request->planillas_ids,
                'nueva_fecha_estimada' => $fecha
            ]);

            $alertaRetraso = null;
            $opcionPosponer = null;
            $elementosIds = $request->elementos_ids ?? [];
            $planillasIds = $request->planillas_ids ?? [];

            // Verificar si son elementos con fecha_entrega propia
            $elementosConFechaPropia = false;
            $fechaAnteriorMax = null;

            if (!empty($elementosIds)) {
                // Comprobar si alguno de estos elementos tiene fecha_entrega propia
                $elementosConFechaPropiaQuery = Elemento::whereIn('id', $elementosIds)
                    ->whereNotNull('fecha_entrega')
                    ->get();

                $elementosConFechaPropia = $elementosConFechaPropiaQuery->isNotEmpty();

                if ($elementosConFechaPropia) {
                    // Obtener fecha anterior desde los elementos
                    foreach ($elementosConFechaPropiaQuery as $elemento) {
                        $fechaElemento = Carbon::parse($elemento->fecha_entrega);
                        if (!$fechaAnteriorMax || $fechaElemento->gt($fechaAnteriorMax)) {
                            $fechaAnteriorMax = $fechaElemento;
                        }
                    }
                }
            }

            // Si no hay elementos con fecha propia, obtener fecha desde las planillas
            if (!$elementosConFechaPropia && is_array($planillasIds) && count($planillasIds) > 0) {
                $planillas = Planilla::whereIn('id', $planillasIds)->get();
                foreach ($planillas as $p) {
                    $fechaRaw = $p->getRawOriginal('fecha_estimada_entrega');
                    if ($fechaRaw) {
                        $fechaPlanilla = Carbon::parse($fechaRaw);
                        if (!$fechaAnteriorMax || $fechaPlanilla->gt($fechaAnteriorMax)) {
                            $fechaAnteriorMax = $fechaPlanilla;
                        }
                    }
                }
            }

            $esPostpone = $fechaAnteriorMax && $fecha->gt($fechaAnteriorMax);

            // Si tenemos IDs de elementos Y se solicitó verificar fabricación
            if (!empty($elementosIds) && $verificarFabricacion) {
                $finProgramadoService = app(\App\Services\FinProgramadoService::class);

                if ($esPostpone) {
                    // Es un postpone: ofrecer retrasar fabricación
                    // Usar método específico para elementos con fecha propia
                    if ($elementosConFechaPropia) {
                        $verificacionRetraso = $finProgramadoService->verificarPosibilidadRetrasoElementos($elementosIds);
                        $puedeRetrasar = $verificacionRetraso['puede_retrasar'];
                        $ordenesAfectadas = $verificacionRetraso['ordenes_afectadas'] ?? [];
                    } else {
                        $verificacionRetraso = $finProgramadoService->verificarPosibilidadRetraso($elementosIds);
                        $puedeRetrasar = $verificacionRetraso['puede_retrasar'];
                        $ordenesAfectadas = $verificacionRetraso['planillas_afectadas'] ?? [];
                    }

                    if ($puedeRetrasar) {
                        $tipoEntrega = $elementosConFechaPropia ? 'de estos elementos' : 'de la planilla';
                        $opcionPosponer = [
                            'mensaje' => "La fecha de entrega {$tipoEntrega} se ha pospuesto. ¿Deseas retrasar también la fabricación para dar prioridad a otros pedidos?",
                            'fecha_anterior' => $fechaAnteriorMax->format('d/m/Y'),
                            'fecha_nueva' => $fecha->format('d/m/Y'),
                            'ordenes_afectadas' => $ordenesAfectadas,
                            'elementos_ids' => $elementosIds,
                            'es_elementos_con_fecha_propia' => $elementosConFechaPropia,
                        ];
                    }
                } else {
                    // Es un adelanto: verificar si llega a tiempo
                    $verificacion = $finProgramadoService->verificarFechaEntrega($elementosIds, $fecha);

                    if (!$verificacion['es_alcanzable']) {
                        $tipoEntrega = $elementosConFechaPropia ? 'de estos elementos' : 'de la planilla';
                        $alertaRetraso = [
                            'mensaje' => $verificacion['mensaje'],
                            'fin_programado' => $verificacion['fin_programado'],
                            'fecha_entrega' => $verificacion['fecha_entrega'],
                            'es_elementos_con_fecha_propia' => $elementosConFechaPropia,
                            'elementos_ids' => $elementosIds,
                        ];
                    }
                }
            }

            if ($elementosConFechaPropia) {
                // 🔹 Actualizar fecha_entrega de los elementos específicos
                // Primero obtener obra_id, fecha anterior y fecha de planilla para actualizar salidas
                $elementosParaSalida = Elemento::whereIn('id', $elementosIds)
                    ->with('planilla:id,obra_id,fecha_estimada_entrega')
                    ->get();
                $obraIdParaSalida = $elementosParaSalida->first()?->planilla?->obra_id;
                $fechaAnteriorParaSalida = $fechaAnteriorMax?->toDateString();

                // 🔹 Verificar si la nueva fecha coincide con la fecha_estimada_entrega de la planilla
                // Si coincide, poner fecha_entrega = null para fusionar con la agrupación de la planilla
                $planilla = $elementosParaSalida->first()?->planilla;
                $fechaPlanilla = $planilla?->fecha_estimada_entrega
                    ? Carbon::parse($planilla->getRawOriginal('fecha_estimada_entrega'))->toDateString()
                    : null;
                $nuevaFechaStr = $fecha->toDateString();

                $fusionarConPlanilla = $fechaPlanilla && $nuevaFechaStr === $fechaPlanilla;

                if ($fusionarConPlanilla) {
                    // Fusionar: quitar fecha_entrega propia para que hereden de la planilla
                    Elemento::whereIn('id', $elementosIds)
                        ->update(['fecha_entrega' => null]);

                    Log::info("🔗 Elementos fusionados con su planilla: fecha_entrega = null", [
                        'elementos_ids' => $elementosIds,
                        'fecha_planilla' => $fechaPlanilla,
                    ]);

                    $logger->logPlanificacion('elementos_fusionados_con_planilla', [
                        'cantidad_elementos' => count($elementosIds),
                        'elementos_ids' => $elementosIds,
                        'fecha_planilla' => $fechaPlanilla,
                        'motivo' => 'Movidos al día de su planilla, se quita fecha_entrega propia',
                    ]);

                    // Registrar en historial de planificación salidas
                    \App\Models\LogPlanificacionSalidas::registrar(
                        'mover_planilla',
                        "ha fusionado " . count($elementosIds) . " elemento(s) con su planilla (fecha: {$fechaPlanilla})",
                        [
                            'elementos_ids' => $elementosIds,
                            'cantidad' => count($elementosIds),
                            'fecha_anterior' => $fechaAnteriorMax?->format('Y-m-d'),
                            'fecha_planilla' => $fechaPlanilla,
                            'tipo' => 'fusion_elementos',
                        ],
                        [],
                        [
                            'elementos_ids' => $elementosIds,
                            'fecha_anterior' => $fechaAnteriorMax?->format('Y-m-d H:i:s'),
                        ]
                    );
                } else {
                    // Mantener como agrupación separada con nueva fecha
                    Elemento::whereIn('id', $elementosIds)
                        ->update(['fecha_entrega' => $fecha]);

                    $logger->logPlanificacion('fecha_elementos_actualizada', [
                        'cantidad_elementos' => count($elementosIds),
                        'elementos_ids' => $elementosIds,
                        'fecha_nueva' => $fecha->format('Y-m-d H:i'),
                    ]);

                    // Registrar en historial de planificación salidas
                    \App\Models\LogPlanificacionSalidas::registrar(
                        'mover_planilla',
                        "ha movido " . count($elementosIds) . " elemento(s) con fecha propia de " . ($fechaAnteriorMax ? $fechaAnteriorMax->format('d/m/Y') : 'S/F') . " a " . $fecha->format('d/m/Y'),
                        [
                            'elementos_ids' => $elementosIds,
                            'cantidad' => count($elementosIds),
                            'fecha_anterior' => $fechaAnteriorMax?->format('Y-m-d'),
                            'fecha_nueva' => $fecha->format('Y-m-d'),
                            'tipo' => 'elementos_fecha_propia',
                        ],
                        [],
                        [
                            'elementos_ids' => $elementosIds,
                            'fecha_anterior' => $fechaAnteriorMax?->format('Y-m-d H:i:s'),
                        ]
                    );
                }

                // 🚚 Actualizar salidas asociadas a esta obra+fecha
                if ($obraIdParaSalida && $fechaAnteriorParaSalida) {
                    $salidasActualizadas = Salida::where('obra_id', $obraIdParaSalida)
                        ->whereDate('fecha_salida', $fechaAnteriorParaSalida)
                        ->update(['fecha_salida' => $fusionarConPlanilla ? $fechaPlanilla : $fecha->toDateString()]);

                    if ($salidasActualizadas > 0) {
                        $fechaDestino = $fusionarConPlanilla ? $fechaPlanilla : $fecha->toDateString();
                        Log::info("🚚 Actualizadas {$salidasActualizadas} salida(s) de obra #{$obraIdParaSalida} de {$fechaAnteriorParaSalida} a {$fechaDestino}");
                    }
                }
            } elseif (is_array($planillasIds) && count($planillasIds) > 0) {
                // 🔥 Actualizar varias planillas
                $planillas = Planilla::whereIn('id', $planillasIds)->get();
                $codigos = $planillas->pluck('codigo')->implode(', ');
                $obraIdParaSalida = $planillas->first()?->obra_id;
                $fechaAnteriorParaSalida = $fechaAnteriorMax?->toDateString();

                Planilla::whereIn('id', $planillasIds)
                    ->update(['fecha_estimada_entrega' => $fecha]);

                // 🚚 Actualizar salidas asociadas a esta obra+fecha
                if ($obraIdParaSalida && $fechaAnteriorParaSalida) {
                    $salidasActualizadas = Salida::where('obra_id', $obraIdParaSalida)
                        ->whereDate('fecha_salida', $fechaAnteriorParaSalida)
                        ->update(['fecha_salida' => $fecha->toDateString()]);

                    if ($salidasActualizadas > 0) {
                        Log::info("🚚 Actualizadas {$salidasActualizadas} salida(s) de obra #{$obraIdParaSalida} de {$fechaAnteriorParaSalida} a {$fecha->toDateString()}");
                    }
                }

                $logger->logPlanificacion('fecha_planillas_actualizada', [
                    'cantidad_planillas' => count($planillasIds),
                    'codigos_planillas' => $codigos,
                    'fecha_nueva' => $fecha->format('Y-m-d H:i'),
                ]);

                // Registrar en historial de planificación salidas
                \App\Models\LogPlanificacionSalidas::registrar(
                    'mover_planilla',
                    "ha movido " . count($planillasIds) . " planilla(s) ({$codigos}) de " . ($fechaAnteriorMax ? $fechaAnteriorMax->format('d/m/Y') : 'S/F') . " a " . $fecha->format('d/m/Y'),
                    [
                        'planillas_ids' => $planillasIds,
                        'codigos' => $codigos,
                        'fecha_anterior' => $fechaAnteriorMax?->format('Y-m-d'),
                        'fecha_nueva' => $fecha->format('Y-m-d'),
                    ],
                    ['planilla_id' => $planillasIds[0] ?? null],
                    [
                        'planillas_ids' => $planillasIds,
                        'fecha_anterior' => $fechaAnteriorMax?->format('Y-m-d H:i:s'),
                    ]
                );
            } else {
                // Por compatibilidad, si solo hay un ID (antiguo método)
                $planilla = Planilla::findOrFail($id);
                $fechaAnterior = $planilla->getRawOriginal('fecha_estimada_entrega');
                $obraIdParaSalida = $planilla->obra_id;
                $fechaAnteriorParaSalida = $fechaAnterior ? Carbon::parse($fechaAnterior)->toDateString() : null;

                $planilla->fecha_estimada_entrega = $fecha;
                $planilla->save();

                // 🚚 Actualizar salidas asociadas a esta obra+fecha
                if ($obraIdParaSalida && $fechaAnteriorParaSalida) {
                    $salidasActualizadas = Salida::where('obra_id', $obraIdParaSalida)
                        ->whereDate('fecha_salida', $fechaAnteriorParaSalida)
                        ->update(['fecha_salida' => $fecha->toDateString()]);

                    if ($salidasActualizadas > 0) {
                        Log::info("🚚 Actualizadas {$salidasActualizadas} salida(s) de obra #{$obraIdParaSalida} de {$fechaAnteriorParaSalida} a {$fecha->toDateString()}");
                    }
                }

                $logger->logPlanificacion('fecha_planilla_actualizada', [
                    'codigo_planilla' => $planilla->codigo ?? 'N/A',
                    'obra' => $planilla->obra->obra ?? 'N/A',
                    'cliente' => $planilla->cliente->empresa ?? 'N/A',
                    'fecha_anterior' => $fechaAnterior ? Carbon::parse($fechaAnterior)->format('Y-m-d H:i') : 'N/A',
                    'fecha_nueva' => $fecha->format('Y-m-d H:i'),
                ]);

                // Registrar en historial de planificación salidas
                \App\Models\LogPlanificacionSalidas::registrar(
                    'mover_planilla',
                    "ha movido la planilla {$planilla->codigo} de " . ($fechaAnterior ? Carbon::parse($fechaAnterior)->format('d/m/Y') : 'S/F') . " a " . $fecha->format('d/m/Y'),
                    [
                        'planilla_id' => $planilla->id,
                        'codigo' => $planilla->codigo,
                        'fecha_anterior' => $fechaAnterior ? Carbon::parse($fechaAnterior)->format('Y-m-d') : null,
                        'fecha_nueva' => $fecha->format('Y-m-d'),
                    ],
                    ['planilla_id' => $planilla->id],
                    [
                        'planilla_id' => $planilla->id,
                        'fecha_anterior' => $fechaAnterior,
                    ]
                );
            }

            // Calcular resúmenes de los días afectados (origen y destino)
            $fechasAfectadas = array_unique(array_filter([
                $fechaAnteriorMax ? $fechaAnteriorMax->format('Y-m-d') : null,
                $fecha->format('Y-m-d'),
            ]));
            $resumenesDias = $this->calcularResumenesDias($fechasAfectadas);

            return response()->json([
                'success' => true,
                'modelo' => 'planilla',
                'alerta_retraso' => $alertaRetraso,
                'opcion_posponer' => $opcionPosponer,
                'resumenes_dias' => $resumenesDias,
            ]);
        }

        return response()->json(['error' => 'Tipo no válido'], 400);
    }

    public function actualizarEmpresaTransporte(Request $request, $id, ActionLoggerService $logger)
    {
        $request->validate([
            'empresa_id' => 'required|integer|exists:empresas_transporte,id'
        ]);

        $salida = Salida::findOrFail($id);
        $empresaAnterior = $salida->empresa_id;
        $empresaAnteriorNombre = $salida->empresaTransporte ? $salida->empresaTransporte->nombre : 'N/A';

        $salida->empresa_id = $request->empresa_id;
        $salida->save();

        // Recargar la relación para obtener el nombre de la nueva empresa
        $salida->load('empresaTransporte');
        $nuevaEmpresaNombre = $salida->empresaTransporte->nombre;

        $logger->logPlanificacion('empresa_transporte_actualizada', [
            'salida_codigo' => $salida->codigo_salida ?? 'N/A',
            'codigo_sage' => $salida->codigo_sage ?? 'N/A',
            'empresa_anterior' => $empresaAnteriorNombre,
            'empresa_nueva' => $nuevaEmpresaNombre,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Empresa de transporte actualizada correctamente',
            'empresa' => [
                'id' => $salida->empresa_id,
                'nombre' => $nuevaEmpresaNombre,
            ]
        ]);
    }

    public function show($id)
    {
        abort(404); // o haz algo según necesites
    }

    /**
     * Simula el adelanto de fabricación para un conjunto de elementos
     */
    public function simularAdelanto(Request $request, FinProgramadoService $finProgramadoService)
    {
        try {
            $request->validate([
                'elementos_ids' => 'required|array',
                'elementos_ids.*' => 'integer',
                'fecha_entrega' => 'required|date',
            ]);

            $elementosIds = $request->elementos_ids;
            $fechaEntrega = $this->parseFlexibleDate($request->fecha_entrega) ?? now();

            $resultado = $finProgramadoService->simularAdelanto($elementosIds, $fechaEntrega);

            return response()->json($resultado);
        } catch (\Throwable $e) {
            \Log::error('[simularAdelanto] Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'necesita_adelanto' => false,
                'mensaje' => 'Error al simular: ' . $e->getMessage(),
                'error' => true,
            ], 500);
        }
    }

    /**
     * Ejecuta el adelanto de fabricación
     */
    public function ejecutarAdelanto(Request $request, FinProgramadoService $finProgramadoService, ActionLoggerService $logger)
    {
        $request->validate([
            'ordenes' => 'required|array',
            'ordenes.*.planilla_id' => 'required|integer',
            'ordenes.*.maquina_id' => 'required|integer',
            'ordenes.*.posicion_nueva' => 'required|integer|min:1',
        ]);

        $resultado = $finProgramadoService->ejecutarAdelanto($request->ordenes);

        if ($resultado['success']) {
            // Registrar en el log
            $logger->logPlanificacion('adelanto_fabricacion_ejecutado', [
                'ordenes_adelantadas' => count($request->ordenes),
                'detalles' => $resultado['resultados'],
            ]);
        }

        return response()->json($resultado);
    }

    /**
     * Simula el retraso de fabricación para un conjunto de elementos
     */
    public function simularRetraso(Request $request, FinProgramadoService $finProgramadoService)
    {
        try {
            $request->validate([
                'elementos_ids' => 'required|array',
                'elementos_ids.*' => 'integer',
            ]);

            $elementosIds = $request->elementos_ids;
            $esElementosConFechaPropia = $request->boolean('es_elementos_con_fecha_propia', false);

            // Detectar automáticamente si son elementos con fecha_entrega propia
            if (!$esElementosConFechaPropia) {
                $esElementosConFechaPropia = Elemento::whereIn('id', $elementosIds)
                    ->whereNotNull('fecha_entrega')
                    ->exists();
            }

            if ($esElementosConFechaPropia) {
                $resultado = $finProgramadoService->simularRetrasoElementos($elementosIds);
            } else {
                $resultado = $finProgramadoService->simularRetraso($elementosIds);
            }

            return response()->json($resultado);
        } catch (\Throwable $e) {
            \Log::error('[simularRetraso] Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'puede_retrasar' => false,
                'mensaje' => 'Error al simular: ' . $e->getMessage(),
                'error' => true,
            ], 500);
        }
    }

    /**
     * Ejecuta el retraso de fabricación
     */
    public function ejecutarRetraso(Request $request, FinProgramadoService $finProgramadoService, ActionLoggerService $logger)
    {
        $request->validate([
            'elementos_ids' => 'required|array',
            'elementos_ids.*' => 'integer',
        ]);

        $elementosIds = $request->elementos_ids;
        $esElementosConFechaPropia = $request->boolean('es_elementos_con_fecha_propia', false);
        $nuevaFechaEntrega = $request->input('nueva_fecha_entrega');

        // Detectar automáticamente si son elementos con fecha_entrega propia
        if (!$esElementosConFechaPropia) {
            $esElementosConFechaPropia = Elemento::whereIn('id', $elementosIds)
                ->whereNotNull('fecha_entrega')
                ->exists();
        }

        if ($esElementosConFechaPropia && $nuevaFechaEntrega) {
            $fecha = $this->parseFlexibleDate($nuevaFechaEntrega) ?? now();
            $resultado = $finProgramadoService->ejecutarRetrasoElementos($elementosIds, $fecha);
        } else {
            $resultado = $finProgramadoService->ejecutarRetraso($elementosIds);
        }

        if ($resultado['success']) {
            $logger->logPlanificacion('retraso_fabricacion_ejecutado', [
                'elementos_afectados' => count($elementosIds),
                'es_elementos_con_fecha_propia' => $esElementosConFechaPropia,
                'detalles' => $resultado['resultados'],
            ]);
        }

        return response()->json($resultado);
    }

    /**
     * Ejecuta el adelanto de fabricación para elementos con fecha_entrega propia
     */
    public function ejecutarAdelantoElementos(Request $request, FinProgramadoService $finProgramadoService, ActionLoggerService $logger)
    {
        $request->validate([
            'elementos_ids' => 'required|array',
            'elementos_ids.*' => 'integer',
            'nueva_fecha_entrega' => 'required|date',
        ]);

        $elementosIds = $request->elementos_ids;
        $fecha = $this->parseFlexibleDate($request->nueva_fecha_entrega) ?? now();

        $resultado = $finProgramadoService->ejecutarAdelantoElementos($elementosIds, $fecha);

        if ($resultado['success']) {
            $logger->logPlanificacion('adelanto_elementos_ejecutado', [
                'elementos_afectados' => count($elementosIds),
                'fecha_nueva' => $fecha->format('Y-m-d H:i'),
                'detalles' => $resultado['resultados'],
            ]);
        }

        return response()->json($resultado);
    }

    // NOTA: Los métodos automatizarSalidas, verificarConflictosPaquetes, procesarAgrupacion,
    // crearSalidaAutomatica y asegurarSalidaClienteEnController fueron eliminados.
    // La automatización ahora es automática via PaqueteObserver:
    // - Cuando se crea un paquete, se busca/crea una salida con obra_id + fecha_salida
    // - Las salidas se pueden crear manualmente desde gestionar-salidas

    /**
     * Obtener logs de planificación de salidas
     */
    public function obtenerLogs(Request $request)
    {
        $limit = $request->input('limit', 50);
        $offset = $request->input('offset', 0);

        $logs = \App\Models\LogPlanificacionSalidas::with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'accion' => $log->accion,
                    'descripcion' => $log->descripcion,
                    'detalles' => $log->detalles,
                    'usuario' => $log->user?->name ?? 'Sistema',
                    'fecha' => $log->created_at->format('d/m/Y H:i:s'),
                    'fecha_relativa' => $log->created_at->diffForHumans(),
                    'puede_revertirse' => $log->puedeRevertirse(),
                    'revertido' => $log->revertido,
                ];
            });

        $total = \App\Models\LogPlanificacionSalidas::count();

        return response()->json([
            'success' => true,
            'logs' => $logs,
            'total' => $total,
            'has_more' => ($offset + $limit) < $total,
        ]);
    }

    /**
     * Revertir un log de planificación de salidas
     */
    public function revertirLog(Request $request)
    {
        $request->validate([
            'log_id' => 'required|integer|exists:logs_planificacion_salidas,id',
        ]);

        $log = \App\Models\LogPlanificacionSalidas::find($request->input('log_id'));

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Log no encontrado',
            ], 404);
        }

        if (!$log->puedeRevertirse()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta acción no puede ser revertida. Solo se puede revertir la última acción no revertida.',
            ], 422);
        }

        try {
            $log->revertir();

            \App\Models\LogPlanificacionSalidas::registrar(
                'revertir_accion',
                "ha revertido: {$log->descripcion}",
                ['log_revertido_id' => $log->id, 'accion_original' => $log->accion]
            );

            return response()->json([
                'success' => true,
                'message' => 'Acción revertida correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al revertir: ' . $e->getMessage(),
            ], 500);
        }
    }
}
