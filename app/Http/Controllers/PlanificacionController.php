<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;
use App\Models\Elemento;
use App\Models\Paquete;
use App\Models\Salida;
use App\Models\Obra;
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
        [$startDate, $endDate] = $this->getDateRange($request);

        $viewType = $request->input('viewType', 'resourceTimeGridDay');

        // Eventos
        $salidasEventos = $this->getEventosSalidas($startDate, $endDate, $viewType);
        $planillasEventos = $this->getEventosPlanillas($startDate, $endDate, $viewType);
        // Pasar los eventos de planillas para calcular el resumen basado en las fechas reales de los eventos
        $resumenEventos = $this->getEventosResumen($planillasEventos['eventos'], $viewType);
        $eventos = collect()
            ->concat($planillasEventos['eventos'])
            ->concat($salidasEventos)
            ->concat($resumenEventos)
            ->concat(Festivo::eventosCalendario())
            ->sortBy([
                // Orden por tipo: planilla (0), salida (1), resumen (2), festivo (3)
                fn($e) => match ($e['tipo'] ?? $e['extendedProps']['tipo'] ?? '') {
                    'planilla' => 0,
                    'salida' => 1,
                    'resumen' => 2,
                    'festivo' => 3,
                    default => 99,
                },
                // Orden secundario: por cod_obra si existe
                fn($e) => $e['extendedProps']['cod_obra'] ?? '',
            ])
            ->values();

        // Resources
        $resources = $this->getResources($eventos, $viewType);

        // AJAX: tipo=all devuelve todo en una sola petición (optimización de rendimiento)
        if ($request->input('tipo') === 'all') {
            $fechaReferencia = $this->parseFlexibleDate($request->input('start')) ?? now();
            $startOfWeek = $fechaReferencia->copy()->startOfWeek(Carbon::MONDAY);
            $endOfWeek = $fechaReferencia->copy()->endOfWeek(Carbon::SUNDAY);

            // Eager load elementos para evitar N+1 queries
            $planillasSemana = Planilla::with('elementos:id,planilla_id,longitud,barras,diametro')
                ->whereBetween('fecha_estimada_entrega', [$startOfWeek, $endOfWeek])
                ->get(['id', 'peso_total', 'fecha_estimada_entrega']);

            $planillasMes = Planilla::with('elementos:id,planilla_id,longitud,barras,diametro')
                ->whereMonth('fecha_estimada_entrega', $fechaReferencia->month)
                ->whereYear('fecha_estimada_entrega', $fechaReferencia->year)
                ->get(['id', 'peso_total', 'fecha_estimada_entrega']);

            return response()->json([
                'events' => $eventos->values(),
                'resources' => $resources,
                'totales' => [
                    'semana' => [
                        'peso' => $planillasSemana->sum('peso_total'),
                        'longitud' => $planillasSemana->flatMap->elementos->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0)),
                        'diametro' => $planillasSemana->flatMap->elementos->pluck('diametro')->filter()->avg(),
                    ],
                    'mes' => [
                        'peso' => $planillasMes->sum('peso_total'),
                        'longitud' => $planillasMes->flatMap->elementos->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0)),
                        'diametro' => $planillasMes->flatMap->elementos->pluck('diametro')->filter()->avg(),
                    ],
                ],
            ]);
        }

        if ($request->input('tipo') === 'resources') {
            return response()->json($resources);
        }
        if ($request->input('tipo') === 'events') {
            return response()->json($eventos->values());
        }

        // Vista normal (no AJAX)
        $fechas = collect(range(0, 13))->map(fn($i) => [
            'fecha' => now()->addDays($i)->format('Y-m-d'),
            'dia' => now()->addDays($i)->locale('es')->translatedFormat('l'),
        ]);

        // 👉 Obtenemos camiones con su empresa
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
            'eventos' => $eventos,
            'empresasTransporte' => $empresasTransporte,
        ]);
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
     */
    public function getTotalesAjax(Request $request)
    {
        $fechaReferencia = $this->parseFlexibleDate($request->input('fecha')) ?? now(); // 👈 usa la fecha de la vista
        $startOfWeek = $fechaReferencia->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $fechaReferencia->copy()->endOfWeek(Carbon::SUNDAY);

        // ✅ semana basada en la fecha visitada
        $planillasSemana = Planilla::whereBetween('fecha_estimada_entrega', [$startOfWeek, $endOfWeek])->get();

        // ✅ mes basado en la fecha visitada
        $planillasMes = Planilla::whereMonth('fecha_estimada_entrega', $fechaReferencia->month)
            ->whereYear('fecha_estimada_entrega', $fechaReferencia->year)
            ->get();

        return response()->json([
            'semana' => [
                'peso' => $planillasSemana->sum('peso_total'),
                'longitud' => $planillasSemana->flatMap->elementos->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0)),
                'diametro' => $planillasSemana->flatMap->elementos->pluck('diametro')->filter()->avg(),
            ],
            'mes' => [
                'peso' => $planillasMes->sum('peso_total'),
                'longitud' => $planillasMes->flatMap->elementos->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0)),
                'diametro' => $planillasMes->flatMap->elementos->pluck('diametro')->filter()->avg(),
            ],
        ]);
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

    private function getEventosSalidas(Carbon $startDate, Carbon $endDate, string $viewType = '')
    {
        $salidas = Salida::with([
            'salidaClientes.obra:id,obra,cod_obra,cliente_id',
            'salidaClientes.obra.cliente:id,empresa',
            'salidaClientes.cliente:id,empresa',
            'paquetes.planilla.obra:id,obra,cod_obra,cliente_id',
            'paquetes.planilla.obra.cliente:id,empresa',
            'paquetes.planilla.user',
            'paquetes.etiquetas', // Para calcular peso desde etiquetas
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

                    $obrasClientes->push([
                        'obra_id' => $relacion->obra_id,
                        'obra' => $relacion->obra?->obra ?? 'Obra desconocida',
                        'cod_obra' => $relacion->obra?->cod_obra ?? '',
                        'cliente' => $clienteNombre,
                        'cliente_id' => $clienteId,
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
                    ])->toArray(),
                    'clientes' => $obrasClientes->map(fn($oc) => [
                        'id' => $oc['cliente_id'],
                        'nombre' => $oc['cliente'],
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
        // 🔹 Traer planillas en el rango con relaciones necesarias
        $planillas = Planilla::with(['obra.cliente', 'elementos', 'paquetes.salidas', 'paquetes.etiquetas'])
            ->whereBetween('fecha_estimada_entrega', [$startDate, $endDate])
            ->get();

        // 🔹 También buscar elementos con fecha_entrega propia en el rango (cuya planilla puede estar fuera)
        $elementosConFechaPropia = Elemento::with(['planilla.obra.cliente'])
            ->whereBetween('fecha_entrega', [$startDate, $endDate])
            ->whereHas('planilla', function ($q) use ($startDate, $endDate) {
                // Excluir los que ya están en planillas del rango principal
                $q->where(function ($sub) use ($startDate, $endDate) {
                    $sub->where('fecha_estimada_entrega', '<', $startDate)
                        ->orWhere('fecha_estimada_entrega', '>', $endDate);
                });
            })
            ->get();

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
            $fechaBase = Carbon::parse($grupo->first()->getRawOriginal('fecha_estimada_entrega'));

            $planillasIds = $grupo->pluck('id')->toArray();

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

        // 👉 Diámetro medio
        $diametros = $planillas->flatMap->elementos->pluck('diametro')->filter();
        $diametroMedio = $diametros->isNotEmpty()
            ? number_format($diametros->avg(), 2, '.', '')
            : null;

        // 👉 Color según estado
        $todasCompletadas = $planillas->every(fn($p) => $p->estado === 'completada');
        $alMenosUnaFabricando = $planillas->contains(fn($p) => $p->estado === 'fabricando');

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
                'cod_obra' => $codObra,
                'nombre_obra' => $nombreObra,
                'cliente' => $clienteNombre,
                'pesoTotal' => $pesoPlanillas,
                'pesoPaquetesSalida' => $pesoPaquetesSalida,
                'longitudTotal' => $planillas->flatMap->elementos->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0)),
                'planillas_ids' => $planillasIds,
                'elementos_ids' => $elementosIds,
                'diametroMedio' => $diametroMedio,
                'todasCompletadas' => $todasCompletadas,
                'tieneSalidas' => $salida !== null,
                'salida_id' => $salida?->id,
                'salida_codigo' => $salidaCodigo,
                'salidas_codigos' => $salidaCodigo ? [$salidaCodigo] : [],
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
            'tipo' => 'required|in:salida,planilla'
        ]);

        $fecha = $this->parseFlexibleDate($request->fecha)?->timezone('Europe/Madrid') ?? now()->timezone('Europe/Madrid');

        if ($request->tipo === 'salida') {
            Log::info('🛠 Actualizando salida', [
                'id' => $id,
                'nueva_fecha_salida' => $fecha->toDateTimeString()
            ]);

            $salida = Salida::findOrFail($id);
            $fechaAnterior = $salida->fecha_salida;
            $salida->fecha_salida = $fecha;
            $salida->save();

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

            return response()->json(['success' => true, 'modelo' => 'salida']);
        }


        if ($request->tipo === 'planilla') {
            Log::info('🛠 Actualizando planillas', [
                'planillas_ids' => $request->planillas_ids,
                'nueva_fecha_estimada' => $fecha
            ]);

            $alertaRetraso = null;
            $elementosIds = $request->elementos_ids ?? [];

            // Si tenemos IDs de elementos, verificar fin programado
            if (!empty($elementosIds)) {
                $finProgramadoService = app(\App\Services\FinProgramadoService::class);
                $verificacion = $finProgramadoService->verificarFechaEntrega($elementosIds, $fecha);

                if (!$verificacion['es_alcanzable']) {
                    $alertaRetraso = [
                        'mensaje' => $verificacion['mensaje'],
                        'fin_programado' => $verificacion['fin_programado'],
                        'fecha_entrega' => $verificacion['fecha_entrega'],
                    ];
                }
            }

            if (is_array($request->planillas_ids) && count($request->planillas_ids) > 0) {
                // 🔥 Actualizar varias planillas
                $planillas = Planilla::whereIn('id', $request->planillas_ids)->get();
                $codigos = $planillas->pluck('codigo')->implode(', ');

                Planilla::whereIn('id', $request->planillas_ids)
                    ->update(['fecha_estimada_entrega' => $fecha]);

                $logger->logPlanificacion('fecha_planillas_actualizada', [
                    'cantidad_planillas' => count($request->planillas_ids),
                    'codigos_planillas' => $codigos,
                    'fecha_nueva' => $fecha->format('Y-m-d H:i'),
                ]);
            } else {
                // Por compatibilidad, si solo hay un ID (antiguo método)
                $planilla = Planilla::findOrFail($id);
                $fechaAnterior = $planilla->getRawOriginal('fecha_estimada_entrega');
                $planilla->fecha_estimada_entrega = $fecha;
                $planilla->save();

                $logger->logPlanificacion('fecha_planilla_actualizada', [
                    'codigo_planilla' => $planilla->codigo ?? 'N/A',
                    'obra' => $planilla->obra->obra ?? 'N/A',
                    'cliente' => $planilla->cliente->empresa ?? 'N/A',
                    'fecha_anterior' => $fechaAnterior ? Carbon::parse($fechaAnterior)->format('Y-m-d H:i') : 'N/A',
                    'fecha_nueva' => $fecha->format('Y-m-d H:i'),
                ]);
            }

            return response()->json([
                'success' => true,
                'modelo' => 'planilla',
                'alerta_retraso' => $alertaRetraso,
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
}
