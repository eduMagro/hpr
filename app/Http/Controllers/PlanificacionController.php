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
    // ✅ Fechas del rango
    $startDate = $request->input('start') ? Carbon::parse($request->input('start'))->startOfDay() : Carbon::now()->startOfMonth();
    $endDate   = $request->input('end')   ? Carbon::parse($request->input('end'))->endOfDay()   : Carbon::now()->endOfMonth();

    // ✅ Salidas filtradas por rango
    $salidas = Salida::with([
        'salidaClientes.obra:id,obra',
        'salidaClientes.cliente:id,empresa',
        'paquetes.planilla.user',
        'empresaTransporte:id,nombre'
    ])
    ->whereBetween('fecha_salida', [$startDate, $endDate])
    ->get();

    // ✅ Planillas filtradas por rango (sin salida)
    $planillas = Planilla::with('obra', 'elementos')
        ->whereDoesntHave('paquetes.salidas')
        ->whereBetween('fecha_estimada_entrega', [$startDate, $endDate])
        ->get();

    // ✅ Obras activas
    $obras = Obra::with('cliente')->where('estado', 'activa')->get();

    // ✅ Obras con salidas y/o planillas
    $obrasConSalidasIds = $salidas->pluck('salidaClientes')->flatten()->pluck('obra_id');
    $obrasPlanillasIds = $planillas->pluck('obra_id');
    $obrasConSalidasIds = $obrasConSalidasIds->merge($obrasPlanillasIds)->unique();

    $obrasConSalidas = Obra::with('cliente')
        ->whereIn('id', $obrasConSalidasIds)
        ->orderBy('obra')
        ->get();

    $obrasConSalidasResources = $obrasConSalidas->map(fn($obra) => [
        'id' => (string) $obra->id,
        'title' => $obra->obra,
        'cliente' => optional($obra->cliente)->empresa,
    ]);

    // ✅ Festivos
    $festivos = $this->getFestivos();

    // ✅ Salidas → eventos
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
                ]
            ];
        });
    });
$eventosPlanillas = $planillas
    ->groupBy(function ($p) {
        if (empty($p->fecha_estimada_entrega)) {
            // Si no hay fecha, evita romper
            return $p->obra_id . '|sin-fecha';
        }

        try {
            $fechaSolo = Carbon::createFromFormat('d/m/Y H:i', $p->fecha_estimada_entrega)->format('Y-m-d');
        } catch (\Exception $e) {
            // Si falla el parseo, márcalo como error o sin-fecha
            $fechaSolo = 'sin-fecha';
        }

        return $p->obra_id . '|' . $fechaSolo;
    })
    ->map(function ($grupoPlanillas) {
        $primera = $grupoPlanillas->first();
        $obraId = $primera->obra_id;
        $nombreObra = optional($primera->obra)->obra ?? 'Obra desconocida';
        $planillasIds = $grupoPlanillas->pluck('id')->toArray();
        $color = '#9CA3AF';

        $fechaInicio = null;
        try {
            $fechaInicio = Carbon::createFromFormat('d/m/Y H:i', $primera->fecha_estimada_entrega);
        } catch (\Exception $e) {
            // Usa fecha actual si está mal
            $fechaInicio = now();
        }

        $pesoTotal = $grupoPlanillas->sum(fn($p) => $p->peso_total ?? 0);
        $longitudTotal = $grupoPlanillas->flatMap->elementos
            ->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0));

        $elementos = $grupoPlanillas->flatMap->elementos;
        $diametros = $elementos->pluck('diametro')->filter();
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
            ]
        ];
    })
    ->values();


    $eventos = collect(array_merge(
        $festivos,
        $salidasEventos->toArray(),
        $eventosPlanillas->toArray()
    ))->map(function ($evento) {
        if (isset($evento['resourceId'])) {
            $evento['resourceId'] = (string) $evento['resourceId'];
        }
        return $evento;
    })->values();

    // ✅ Respuesta JSON según lo que pide el calendario
    if ($request->wantsJson() && $request->query('resources')) {
        return response()->json($obrasConSalidasResources);
    }

    if ($request->wantsJson()) {
        return response()->json($eventos);
    }

    // ✅ Vista normal
    $fechas = collect(range(0, 13))->map(fn($i) => [
        'fecha' => now()->addDays($i)->format('Y-m-d'),
        'dia' => now()->addDays($i)->locale('es')->translatedFormat('l')
    ]);

    return view('planificacion.index', compact(
        'fechas',
        'eventos',
        'obrasConSalidas',
        'obras',
        'obrasConSalidasResources'
    ));
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
}
