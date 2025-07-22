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
       // 🔹 Obtener las salidas con relaciones necesarias
$salidas = Salida::with([
    'salidaClientes.obra:id,obra',
    'salidaClientes.cliente:id,empresa',
    'paquetes.planilla.user',
    'empresaTransporte:id,nombre'
])->get();

// 🔹 Planillas que aún no tienen salida
$planillas = Planilla::with('obra', 'elementos')
    ->whereDoesntHave('paquetes.salidas')
    ->get();

// 🔹 Obras activas
$obras = Obra::with('cliente')->where('estado', 'activa')->get();

// 🔹 IDs de obras con salidas
$obrasConSalidasIds = $salidas->pluck('salidaClientes')->flatten()
    ->pluck('obra_id')
    ->unique()
    ->filter();

// 🔹 IDs de obras que tienen planillas agrupadas
$obrasPlanillasIds = $planillas->pluck('obra_id')->unique()->filter();

// 🔹 Unimos ambos
$obrasConSalidasIds = $obrasConSalidasIds
    ->merge($obrasPlanillasIds)
    ->unique();

// 🔹 Obtener obras finales
$obrasConSalidas = Obra::with('cliente')
    ->whereIn('id', $obrasConSalidasIds)
    ->orderBy('obra')
    ->get();



        $obrasConSalidasResources = $obrasConSalidas->map(fn($obra) => [
            'id' => (string) $obra->id,
            'title' => $obra->obra,
            'cliente' => optional($obra->cliente)->empresa,
        ]);

        $resources = $obras->map(fn($obra) => [
            'id' => (string) $obra->id,
            'title' => $obra->obra,
            'cliente' => optional($obra->cliente)->empresa,
        ]);

        $todasLasObras = $resources;

        // 🔹 Festivos
        $festivos = $this->getFestivos();

        // 🔹 Fechas
        $fechas = collect(range(0, 13))->map(fn($i) => [
            'fecha' => now()->addDays($i)->format('Y-m-d'),
            'dia' => now()->addDays($i)->locale('es')->translatedFormat('l')
        ]);

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
                    'id' => $salida->id . '-' . $obra->id, // ID combinado para que sea único por evento
                    'start' => $fechaInicio->toDateTimeString(),
                    'end' => $fechaFin->toDateTimeString(),
                    'resourceId' => $obra->id,
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

        // 🔹 Planillas agrupadas por obra y fecha
        $planillas = Planilla::with('obra')
            ->whereDoesntHave('paquetes.salidas')
            ->get();

        $planillas = Planilla::with('obra', 'elementos')
            ->whereDoesntHave('paquetes.salidas') // Mantienes esta condición
            ->get();

        $eventosPlanillas = $planillas
            ->groupBy(function ($p) {
        // Convertimos la fecha completa a solo Y-m-d
        $fechaSolo = Carbon::createFromFormat('d/m/Y H:i', $p->fecha_estimada_entrega)->format('Y-m-d');
        return $p->obra_id . '|' . $fechaSolo;
    })
            ->map(function ($grupoPlanillas) {
                $obraId = $grupoPlanillas->first()->obra_id;
                $nombreObra = optional($grupoPlanillas->first()->obra)->obra ?? 'Obra desconocida';
                $planillasIds = $grupoPlanillas->pluck('id')->toArray();
                $color = '#9CA3AF';

                $fechaInicio = Carbon::createFromFormat('d/m/Y H:i', $grupoPlanillas->first()->fecha_estimada_entrega);

                // 🔢 Peso total de las planillas
                $pesoTotal = $grupoPlanillas->sum(fn($p) => $p->peso_total ?? 0);

                // 📏 Longitud total: suma de (longitud * barras) de todos los elementos de esas planillas
                $longitudTotal = $grupoPlanillas->flatMap->elementos
                    ->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0));

                // 🔘 Diámetro medio
                $elementos = $grupoPlanillas->flatMap->elementos;
                $diametros = $elementos->pluck('diametro')->filter(); // elimina nulos y ceros si quieres ->filter(fn($d) => $d > 0)
                $diametroMedio = $diametros->isNotEmpty() ? round($diametros->avg(), 2) : null;

                return [
                    'title' => "{$nombreObra}",
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
                $evento['resourceId'] = (string) $evento['resourceId']; // 🔥 Forzamos aquí antes del @json
            }
            return $evento;
        })->values();


        // 🔥 Si pide JSON (AJAX), devolvemos solo los eventos
        if ($request->wantsJson()) {
            return response()->json($eventos);
        }

        // 🖥 Si es carga normal, renderizamos la vista
        return view('planificacion.index', compact(
            'fechas',
            'eventos',
            'obrasConSalidas',
            'obras',
            'todasLasObras',
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
