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


    // 📌 Rango de fechas desde el calendario (AJAX)
    $start = $request->input('start');
    $end   = $request->input('end');

    $startDate = $start ? Carbon::parse($start)->startOfDay() : Carbon::now()->startOfMonth();
    $endDate   = $end   ? Carbon::parse($end)->endOfDay()   : Carbon::now()->endOfMonth();
Log::info('🧭 Fechas filtradas', [
    'startDate' => $startDate->toDateTimeString(),
    'endDate'   => $endDate->toDateTimeString()
]);
    // 🔹 Salidas filtradas
    $salidas = Salida::with([
        'salidaClientes.obra:id,obra',
        'salidaClientes.cliente:id,empresa',
        'paquetes.planilla.user',
        'empresaTransporte:id,nombre'
    ])
    ->whereBetween('fecha_salida', [$startDate, $endDate])
    ->get();

$planillas = Planilla::with('obra', 'elementos')
    ->whereDoesntHave('paquetes.salidas')
    ->whereBetween('fecha_estimada_entrega', [$startDate, $endDate])
    ->get();


    // 🔹 Eventos de salidas
    $salidasEventos = $salidas->flatMap(function ($salida) {
        $empresa = optional($salida->empresaTransporte)->nombre;
        $pesoTotal = round($salida->paquetes->sum(fn($p) => optional($p->planilla)->peso_total ?? 0), 0);
        $fechaInicio = Carbon::parse($salida->fecha_salida);
        $fechaFin = $fechaInicio->copy()->addHours(3);
        $color = $salida->estado === 'completada' ? '#4CAF50' : '#3B82F6';

        return $salida->salidaClientes->map(function ($relacion) use ($salida, $empresa, $pesoTotal, $fechaInicio, $fechaFin, $color) {
            $obra = $relacion->obra;
            $nombreObra = optional($obra)->obra ?? 'Obra desconocida';

            return [
                'title' => "{$salida->codigo_salida} - {$nombreObra} - {$pesoTotal} kg",
                'id' => $salida->id . '-' . $obra->id,
                'start' => $fechaInicio->toDateTimeString(),
                'end' => $fechaFin->toDateTimeString(),
                'resourceId' => (string)$obra->id,
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

    // 🔹 Eventos de planillas agrupadas por obra y fecha
    $eventosPlanillas = $planillas
        ->groupBy(function ($p) {
            $fechaSolo = Carbon::createFromFormat('d/m/Y H:i', $p->fecha_estimada_entrega)->format('Y-m-d');
            return $p->obra_id . '|' . $fechaSolo;
        })
        ->map(function ($grupo) {
            $obraId = $grupo->first()->obra_id;
            $nombreObra = optional($grupo->first()->obra)->obra ?? 'Obra desconocida';
            $planillasIds = $grupo->pluck('id')->toArray();
            $color = '#9CA3AF';

            $fechaInicio = Carbon::createFromFormat('d/m/Y H:i', $grupo->first()->fecha_estimada_entrega);

            $pesoTotal = $grupo->sum(fn($p) => $p->peso_total ?? 0);
            $longitudTotal = $grupo->flatMap->elementos->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0));
            $diametros = $grupo->flatMap->elementos->pluck('diametro')->filter();
            $diametroMedio = $diametros->isNotEmpty() ? round($diametros->avg(), 2) : null;

            return [
                'title' => $nombreObra,
                'id' => 'planillas-' . $obraId . '-' . md5($fechaInicio),
                'start' => $fechaInicio->toIso8601String(),
                'end' => $fechaInicio->copy()->addHours(2)->toIso8601String(),
                'resourceId' => (string)$obraId,
                'allDay' => false,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'tipo' => 'planilla',
                'extendedProps' => [
                    'tipo' => 'planilla',
                    'pesoTotal' => $pesoTotal,
                    'longitudTotal' => $longitudTotal,
                    'planillas_ids' => $planillasIds,
                    'diametroMedio' => $diametroMedio,
                ],
            ];
        })
        ->values();

    // 🔹 Unir eventos
    $eventos = collect(array_merge(
        $this->getFestivos(),
        $salidasEventos->toArray(),
        $eventosPlanillas->toArray()
    ));

    // 🎯 FILTRAR resources SOLO para los resourceId presentes en los eventos
    $resourceIdsConEventos = $eventos->pluck('resourceId')->filter()->unique()->values();
    $obrasConSalidas = Obra::with('cliente')
        ->whereIn('id', $resourceIdsConEventos)
        ->orderBy('obra')
        ->get();

    $obrasConSalidasResources = $obrasConSalidas->map(fn($obra) => [
        'id'    => (string)$obra->id,
        'title' => $obra->obra,
        'cliente' => optional($obra->cliente)->empresa,
        'cod_obra' => $obra->cod_obra,
    ])->values();

    // ✅ RESPONDER JSON SEGÚN 'tipo'
    if ($request->input('tipo') === 'resources') {
        return response()->json($obrasConSalidasResources);
    }
    if ($request->input('tipo') === 'events') {
        return response()->json($eventos->values());
    }

    // 🖥 Vista normal
    $fechas = collect(range(0, 13))->map(fn($i) => [
        'fecha' => now()->addDays($i)->format('Y-m-d'),
        'dia' => now()->addDays($i)->locale('es')->translatedFormat('l')
    ]);

Log::info('🎯 Eventos generados', $eventos->toArray());
return view('planificacion.index', [
    'fechas' => $fechas,
]);


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
