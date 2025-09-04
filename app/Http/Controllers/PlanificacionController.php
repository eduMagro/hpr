<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;
use App\Models\Paquete;
use App\Models\Salida;
use App\Models\Obra;
use App\Models\Camion;
use App\Models\Festivo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AlertaService;
use App\Models\Departamento;

class PlanificacionController extends Controller
{
    public function index(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);

        $viewType = $request->input('viewType', 'resourceTimelineDay');

        // Eventos
        $salidasEventos = $this->getEventosSalidas($startDate, $endDate);
        $planillasEventos = $this->getEventosPlanillas($startDate, $endDate);
        $resumenEventos = $this->getEventosResumen($planillasEventos['planillas'], $viewType);
        $eventos = collect()
            ->concat($planillasEventos['eventos'])
            ->concat($salidasEventos)
            ->concat($resumenEventos)
            ->concat(Festivo::eventosCalendario())
            ->sort(function ($a, $b) {
                // 1) prioridad por tipo
                $rank = function ($e) {
                    $t = $e['tipo'] ?? ($e['extendedProps']['tipo'] ?? '');
                    return match ($t) {
                        'planilla' => 0,
                        'salida'   => 1,
                        'resumen'  => 2,
                        'festivo'  => 3,
                        default    => 99,
                    };
                };

                // 2) cod_obra como número (siempre es numérico)
                $numCod = function ($e) {
                    $codObra = $e['extendedProps']['cod_obra'] ?? '';
                    return is_numeric($codObra) ? (int)$codObra : PHP_INT_MAX;
                };

                $ra = $rank($a);
                $rb = $rank($b);
                if ($ra !== $rb) return $ra <=> $rb;

                return $numCod($a) <=> $numCod($b);
            })
            ->values();

        // Resources
        $resources = $this->getResources($eventos);
        if ($request->input('tipo') === 'resources') {
            return response()->json($resources); // ✅ usa la variable correcta
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
        return view('planificacion.index', [
            'fechas' => $fechas,
            'camiones' => $camiones,
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
        if ($viewType === 'resourceTimelineDay' && $start) {
            $fecha = Carbon::parse($start)->startOfDay();
            return [$fecha, $fecha->copy()->endOfDay()];
        }

        // Vista semana
        if ($viewType === 'resourceTimelineWeek' && $start && $end) {
            return [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->subSecond()
            ];
        }

        // Vista mes
        if ($viewType === 'dayGridMonth' && $start && $end) {
            return [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->subSecond()
            ];
        }

        // Por defecto
        return [
            $start ? Carbon::parse($start)->startOfDay() : now()->startOfMonth(),
            $end ? Carbon::parse($end)->subSecond() : now()->endOfMonth()
        ];
    }
    /**
     * Obtiene los totales de planillas y elementos para la semana y mes basados en la fecha de la vista.
     */
    public function getTotalesAjax(Request $request)
    {
        $fechaReferencia = Carbon::parse($request->input('fecha')); // 👈 usa la fecha de la vista
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
     * Obtiene los eventos de resumen y planillas
     * @param \Illuminate\Support\Collection $planillas
     * @param string $viewType
     * @return \Illuminate\Support\Collection
     */
    private function getEventosResumen($planillas, string $viewType)
    {
        // ------------------- RESUMEN POR DÍA -------------------
        $resumenPorDia = $planillas
            ->groupBy(fn($p) => Carbon::parse($p->getRawOriginal('fecha_estimada_entrega'))->toDateString())
            ->map(fn($grupo, $fechaDia) => [
                'fecha' => Carbon::parse($fechaDia),
                'pesoTotal' => $grupo->sum('peso_total'),
                'longitudTotal' => $grupo->flatMap->elementos->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0)),
                'diametroMedio' => $grupo->flatMap->elementos->pluck('diametro')->filter()->avg(),
            ])->values();

        $resumenEventosDia = $resumenPorDia->map(function ($r) {
            $titulo = "📦 " . number_format($r['pesoTotal'], 0, ',', '.') . " kg | 📏 " . number_format($r['longitudTotal'], 0, ',', '.') . " m";
            if ($r['diametroMedio'] !== null) {
                $titulo .= " | ⌀ " . number_format($r['diametroMedio'], 2, '.', '') . " mm";
            }
            return [
                'title' => $titulo,
                'start' => $r['fecha']->startOfDay()->toIso8601String(),
                'end' => $r['fecha']->endOfDay()->toIso8601String(),
                'resourceId' => 'resumen-dia',
                'allDay' => true,
                'backgroundColor' => '#fbbf24',
                'borderColor' => '#92400e',
                'textColor' => '#000',
                'tipo' => 'resumen-dia',
            ];
        });

        // ------------------- SELECCIÓN SEGÚN VISTA -------------------
        $eventosResumen = collect();


        $eventosResumen = $eventosResumen->merge($resumenEventosDia);


        return $eventosResumen;
    }

    private function getEventosSalidas(Carbon $startDate, Carbon $endDate)
    {
        $salidas = Salida::with([
            // añade cod_obra aquí 👇
            'salidaClientes.obra:id,obra,cod_obra',
            'salidaClientes.cliente:id,empresa',
            'paquetes.planilla.user',
            'empresaTransporte:id,nombre',
            'camion:id,modelo', // si usas $salida->camion->modelo
        ])
            ->whereBetween('fecha_salida', [$startDate, $endDate])
            ->get();

        return $salidas->flatMap(function ($salida) {
            $empresa    = optional($salida->empresaTransporte)->nombre;
            $camion     = optional($salida->camion)->modelo;
            $pesoTotal  = round($salida->paquetes->sum(fn($p) => optional($p->planilla)->peso_total ?? 0), 0);
            $fechaInicio = Carbon::parse($salida->fecha_salida);
            $fechaFin   = $fechaInicio->copy()->addHours(3);
            $color      = $salida->estado === 'completada' ? '#4CAF50' : '#3B82F6';

            return $salida->salidaClientes->map(function ($relacion) use ($salida, $empresa, $camion, $pesoTotal, $fechaInicio, $fechaFin, $color) {
                $obra = $relacion->obra;

                return [
                    'title'        => "{$salida->codigo_salida} - {$salida->codigo_sage} - {$obra->obra} - {$pesoTotal} kg",
                    'id'           => $salida->id . '-' . $obra->id,
                    'start'        => $fechaInicio->toDateTimeString(),
                    'end'          => $fechaFin->toDateTimeString(),
                    'resourceId'   => (string) $obra->id,
                    'tipo'         => 'salida',
                    'backgroundColor' => $color,
                    'borderColor'     => $color,
                    'extendedProps' => [
                        'tipo'         => 'salida',
                        'cod_obra'     => $obra->cod_obra,   // <- ahora sí existe
                        'nombre_obra'  => $obra->obra,       // <- para buscar por nombre
                        'empresa'      => $empresa,
                        'camion'       => $camion,
                        'comentario'   => $salida->comentario,
                    ],
                ];
            });
        });
    }

    /**
     * Obtiene los eventos de planillas agrupados por obra y día.
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getEventosPlanillas(Carbon $startDate, Carbon $endDate): array
    {
        // 🔹 Traer planillas sin salidas en el rango
        $planillas = Planilla::with('obra', 'elementos')
            ->whereBetween('fecha_estimada_entrega', [$startDate, $endDate])
            ->get();


        // 🔹 Agrupar por obra y día
        $eventos = $planillas->groupBy(function ($p) {
            return $p->obra_id . '|' . Carbon::parse($p->getRawOriginal('fecha_estimada_entrega'))->toDateString();
        })->map(function ($grupo) {
            $obraId = $grupo->first()->obra_id;
            $obra = $grupo->first()->obra;
            $nombreObra = optional($obra)->obra ?? 'Obra desconocida';
            $codObra = optional($obra)->cod_obra ?? 'Código desconocido';


            $fechaInicio = Carbon::parse($grupo->first()->getRawOriginal('fecha_estimada_entrega'))->setTime(6, 0, 0);

            // 👉 IDs de planillas agrupadas
            $planillasIds = $grupo->pluck('id')->toArray();

            // 👉 Totales por estado
            $fabricados = $grupo->where('estado', 'completada')->sum(fn($p) => $p->peso_total ?? 0);
            $fabricando = $grupo->where('estado', 'fabricando')->sum(fn($p) => $p->peso_total ?? 0);
            $pendientes = $grupo->where('estado', 'pendiente')->sum(fn($p) => $p->peso_total ?? 0);

            // 👉 Diámetro medio
            $diametros = $grupo->flatMap->elementos->pluck('diametro')->filter();
            $diametroMedio = $diametros->isNotEmpty()
                ? number_format($diametros->avg(), 2, '.', '')
                : null;

            // 👉 Color según estado
            $todasCompletadas = $grupo->every(fn($p) => $p->estado === 'completada');
            $alMenosUnaFabricando = $grupo->contains(fn($p) => $p->estado === 'fabricando');

            if ($todasCompletadas) {
                $color = '#22c55e'; // verde
            } elseif ($alMenosUnaFabricando) {
                $color = '#facc15'; // amarillo
            } else {
                $color = '#9CA3AF'; // gris
            }


            // Antes del return de cada evento, obtén todas las salidas relacionadas con esas planillas
            // 👉 Buscar primero los paquetes asociados a esas planillas
            $paqueteIds = Paquete::whereIn('planilla_id', $planillasIds)->pluck('id');

            // 👉 Buscar las salidas que tengan esos paquetes en la tabla pivote
            $salidaRelacionada = Salida::whereHas('paquetes', function ($q) use ($paqueteIds) {
                $q->whereIn('paquete_id', $paqueteIds);
            })->get();

            return [
                'title' => $codObra . ' - ' . $nombreObra,
                'id' => 'planillas-' . $obraId . '-' . md5($fechaInicio),
                // 'start' => $fechaInicio->toIso8601String(),
                // 'end' => $fechaInicio->copy()->addHours(2)->toIso8601String(),
                'start' => $fechaInicio->toDateString(), // 👈 solo la fecha
                'end' => $fechaInicio->copy()->addDay()->toDateString(), // 👈 fecha siguiente
                'allDay' => true, // 👈 esto es CLAVE para dayGridMonth
                'backgroundColor' => $color,
                'borderColor' => $color,
                'tipo' => 'planilla',
                'extendedProps' => [
                    'tipo' => 'planilla',
                    'cod_obra' => $codObra,
                    'nombre_obra'  => $nombreObra,
                    'pesoTotal' => $grupo->sum(fn($p) => $p->peso_total ?? 0),
                    'longitudTotal' => $grupo->flatMap->elementos->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0)),
                    'planillas_ids' => $planillasIds,
                    'diametroMedio' => $diametroMedio,
                    'fabricadosKg' => $fabricados,
                    'fabricandoKg' => $fabricando,
                    'pendientesKg' => $pendientes,
                    'todasCompletadas' => $todasCompletadas,
                    'tieneSalidas' => $salidaRelacionada->count() > 0,
                    'salidas_codigos' => $salidaRelacionada->pluck('codigo_salida')->toArray(),
                ],
            ];
        })->values();

        return [
            'planillas' => $planillas,
            'eventos' => $eventos,
        ];
    }

    private function getResources($eventos)
    {

        $resourceIds = $eventos->pluck('resourceId')->filter()->unique()->values();
        $obras = Obra::with('cliente')->whereIn('id', $resourceIds)->get()
            ->sortBy(function ($obra) {
                return is_numeric($obra->cod_obra) ? (int)$obra->cod_obra : PHP_INT_MAX;
            });

        $resources = $obras->map(fn($obra) => [
            'id' => (string) $obra->id,
            'title' => $obra->obra,
            'cliente' => optional($obra->cliente)->empresa,
            'cod_obra' => $obra->cod_obra,
            'orderIndex' => 2,
        ])->values();

        $resources->prepend([
            'id' => 'resumen-dia',
            'title' => '📊 Resumen Diario',
            'cliente' => '',
            'cod_obra' => '',
            'orderIndex' => 1,
        ]);

        return $resources;
    }

    public function guardarComentario(Request $request, $id)
    {
        $request->validate([
            'comentario' => 'nullable|string|max:1000'
        ]);

        $salida = Salida::findOrFail($id);
        $salida->comentario = $request->comentario;
        $salida->save();

        return response()->json(['success' => true]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'fecha' => 'required|date',
            'tipo' => 'required|in:salida,planilla'
        ]);

        $fecha = Carbon::parse($request->fecha)->timezone('Europe/Madrid');

        if ($request->tipo === 'salida') {
            Log::info('🛠 Actualizando salida', [
                'id' => $id,
                'nueva_fecha_salida' => $fecha->toDateTimeString()
            ]);

            $salida = Salida::findOrFail($id);
            $salida->fecha_salida = $fecha;
            $salida->save();

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

            if (is_array($request->planillas_ids) && count($request->planillas_ids) > 0) {
                // 🔥 Actualizar varias planillas
                Planilla::whereIn('id', $request->planillas_ids)
                    ->update(['fecha_estimada_entrega' => $fecha]);
            } else {
                // Por compatibilidad, si solo hay un ID (antiguo método)
                $planilla = Planilla::findOrFail($id);
                $planilla->fecha_estimada_entrega = $fecha;
                $planilla->save();
            }

            return response()->json(['success' => true, 'modelo' => 'planilla']);
        }

        return response()->json(['error' => 'Tipo no válido'], 400);
    }

    public function show($id)
    {
        abort(404); // o haz algo según necesites
    }
}
