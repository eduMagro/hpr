<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;
use App\Models\Paquete;
use App\Models\Salida;
use App\Models\Obra;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        $eventos = collect(array_merge(
            $this->getFestivos(),
            $salidasEventos->toArray(),
            $planillasEventos['eventos']->toArray(),
            $resumenEventos->toArray()
        ));

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

        return view('planificacion.index', [
            'fechas' => $fechas,
        ]);
    }


    private function getDateRange(Request $request): array
    {
        $start = $request->input('start');
        $end = $request->input('end');

        $startDate = $start ? Carbon::parse($start)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $end ? Carbon::parse($end)->endOfDay() : Carbon::now()->endOfMonth();

        return [$startDate, $endDate];
    }
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

        // ------------------- RESUMEN POR SEMANA -------------------
        $resumenPorSemana = $planillas
            ->groupBy(fn($p) => Carbon::parse($p->getRawOriginal('fecha_estimada_entrega'))->format('o-W'))
            ->map(fn($grupo, $semanaKey) => [
                'fecha' => Carbon::parse($grupo->min(fn($p) => $p->getRawOriginal('fecha_estimada_entrega'))),
                'pesoTotal' => $grupo->sum('peso_total'),
                'longitudTotal' => $grupo->flatMap->elementos->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0)),
                'diametroMedio' => $grupo->flatMap->elementos->pluck('diametro')->filter()->avg(),
                'semana' => $semanaKey,
            ])->values();

        $resumenEventosSemana = $resumenPorSemana->map(function ($r) {
            $titulo = "🗓️ Semana {$r['semana']} → " . number_format($r['pesoTotal'], 0, ',', '.') . " kg";
            return [
                'title' => $titulo,
                'start' => $r['fecha']->startOfWeek()->toIso8601String(),
                'end' => $r['fecha']->endOfWeek()->toIso8601String(),
                'resourceId' => 'resumen-semana',
                'allDay' => true,
                'backgroundColor' => '#60a5fa',
                'borderColor' => '#1e3a8a',
                'textColor' => '#fff',
                'tipo' => 'resumen-semana',
            ];
        });

        // ------------------- RESUMEN POR MES -------------------
        $resumenPorMes = $planillas
            ->groupBy(fn($p) => Carbon::parse($p->getRawOriginal('fecha_estimada_entrega'))->format('Y-m'))
            ->map(fn($grupo, $mesKey) => [
                'fecha' => Carbon::parse($grupo->min(fn($p) => $p->getRawOriginal('fecha_estimada_entrega'))),
                'pesoTotal' => $grupo->sum('peso_total'),
                'longitudTotal' => $grupo->flatMap->elementos->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0)),
                'diametroMedio' => $grupo->flatMap->elementos->pluck('diametro')->filter()->avg(),
                'mes' => $mesKey,
            ])->values();

        $resumenEventosMes = $resumenPorMes->map(function ($r) {
            $titulo = "📅 Mes {$r['mes']} → " . number_format($r['pesoTotal'], 0, ',', '.') . " kg";
            return [
                'title' => $titulo,
                'start' => $r['fecha']->startOfMonth()->toIso8601String(),
                'end' => $r['fecha']->endOfMonth()->toIso8601String(),
                'resourceId' => 'resumen-mes',
                'allDay' => true,
                'backgroundColor' => '#34d399',
                'borderColor' => '#065f46',
                'textColor' => '#000',
                'tipo' => 'resumen-mes',
            ];
        });

        // ------------------- SELECCIÓN SEGÚN VISTA -------------------
        $eventosResumen = collect();

        if ($viewType === 'resourceTimelineDay') {
            $eventosResumen = $eventosResumen->merge($resumenEventosDia);
        }
        if ($viewType === 'resourceTimelineWeek') {
            $eventosResumen = $eventosResumen->merge($resumenEventosDia)->merge($resumenEventosSemana);
        }
        if ($viewType === 'dayGridMonth') {
            $eventosResumen = $eventosResumen->merge($resumenEventosDia)->merge($resumenEventosSemana)->merge($resumenEventosMes);
        }

        return $eventosResumen;
    }

    private function getEventosSalidas(Carbon $startDate, Carbon $endDate)
    {
        $salidas = Salida::with([
            'salidaClientes.obra:id,obra',
            'salidaClientes.cliente:id,empresa',
            'paquetes.planilla.user',
            'empresaTransporte:id,nombre'
        ])->whereBetween('fecha_salida', [$startDate, $endDate])->get();

        return $salidas->flatMap(function ($salida) {
            $empresa = optional($salida->empresaTransporte)->nombre;
            $pesoTotal = round($salida->paquetes->sum(fn($p) => optional($p->planilla)->peso_total ?? 0), 0);
            $fechaInicio = Carbon::parse($salida->fecha_salida);
            $fechaFin = $fechaInicio->copy()->addHours(3);
            $color = $salida->estado === 'completada' ? '#4CAF50' : '#3B82F6';

            return $salida->salidaClientes->map(function ($relacion) use ($salida, $empresa, $pesoTotal, $fechaInicio, $fechaFin, $color) {
                $obra = $relacion->obra;
                return [
                    'title' => "{$salida->codigo_salida} - {$obra->obra} - {$pesoTotal} kg",
                    'id' => $salida->id . '-' . $obra->id,
                    'start' => $fechaInicio->toDateTimeString(),
                    'end' => $fechaFin->toDateTimeString(),
                    'resourceId' => (string) $obra->id,
                    'tipo' => 'salida',
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'empresa' => $empresa,
                        'tipo' => 'salida',
                        'comentario' => $salida->comentario,
                    ],
                ];
            });
        });
    }
    private function getEventosPlanillas(Carbon $startDate, Carbon $endDate): array
    {
        // 🔹 Traer planillas sin salidas en el rango
        $planillas = Planilla::with('obra', 'elementos')
            ->whereBetween('fecha_estimada_entrega', [$startDate, $endDate])

            ->get();
        \Log::warning('Fechas del rango', [
            'startDate' => $startDate->toDateTimeString(),
            'endDate' => $endDate->toDateTimeString(),
        ]);

        // 🔹 Agrupar por obra y día
        $eventos = $planillas->groupBy(function ($p) {
            return $p->obra_id . '|' . Carbon::parse($p->getRawOriginal('fecha_estimada_entrega'))->toDateString();
        })->map(function ($grupo) {
            $obraId = $grupo->first()->obra_id;
            $nombreObra = optional($grupo->first()->obra)->obra ?? 'Obra desconocida';
            $fechaInicio = Carbon::parse($grupo->first()->getRawOriginal('fecha_estimada_entrega'));

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
            $color = $todasCompletadas ? '#22c55e' : '#9CA3AF';

            return [
                'title' => $nombreObra,
                'id' => 'planillas-' . $obraId . '-' . md5($fechaInicio),
                'start' => $fechaInicio->toIso8601String(),
                'end' => $fechaInicio->copy()->addHours(2)->toIso8601String(),
                'resourceId' => (string) $obraId,
                'allDay' => false,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'tipo' => 'planilla',
                'extendedProps' => [
                    'tipo' => 'planilla',
                    'pesoTotal' => $grupo->sum(fn($p) => $p->peso_total ?? 0),
                    'longitudTotal' => $grupo->flatMap->elementos->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0)),
                    'planillas_ids' => $planillasIds,
                    'diametroMedio' => $diametroMedio,
                    'fabricadosKg' => $fabricados,
                    'fabricandoKg' => $fabricando,
                    'pendientesKg' => $pendientes,
                    'todasCompletadas' => $todasCompletadas,
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
        $obras = Obra::with('cliente')->whereIn('id', $resourceIds)->orderBy('obra')->get();

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




    private function getFestivos()
    {
        $response = Http::get("https://date.nager.at/api/v3/PublicHolidays/" . date('Y') . "/ES");

        if ($response->failed()) {
            return []; // Si la API falla, devolvemos un array vacío
        }

        $festivos = collect($response->json())->filter(function ($holiday) {
            // Si no tiene 'counties', es un festivo NACIONAL
            if (!isset($holiday['counties'])) {
                return true;
            }
            // Si el festivo pertenece a Andalucía
            return in_array('ES-AN', $holiday['counties']);
        })->map(function ($holiday) {
            return [
                'title' => $holiday['localName'], // Nombre del festivo
                'start' => Carbon::parse($holiday['date'])->toDateString(), // Fecha formateada correctamente
                'backgroundColor' => '#ff0000', // Rojo para festivos
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'allDay' => true,
                'tipo' => 'festivo'
            ];
        });

        // Añadir festivos locales de Los Palacios y Villafranca
        $festivosLocales = collect([
            [
                'title' => 'Festividad de Nuestra Señora de las Nieves',
                'start' => date('Y') . '-08-05',
                'backgroundColor' => '#ff0000',
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'allDay' => true,
                'tipo' => 'festivo'
            ],
            [
                'title' => 'Feria Los Palacios y Vfca',
                'start' => date('Y') . '-09-25',
                'backgroundColor' => '#ff0000',
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'allDay' => true,
                'tipo' => 'festivo'
            ]
        ]);

        // Combinar festivos nacionales, autonómicos y locales
        return $festivos->merge($festivosLocales)->values()->toArray();
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
