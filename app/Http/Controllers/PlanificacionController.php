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
    public function index()
    {
        // 🔹 Obtener las salidas con relaciones necesarias
        $salidas = Salida::with([
            'salidaClientes.obra:id,obra',
            'salidaClientes.cliente:id,empresa',
            'paquetes.planilla.user',
            'empresaTransporte:id,nombre'
        ])->get();

        // 🔹 Obtener todas las obras activas como resources
        $obras = Obra::with('cliente')->where('estado', 'activa')->get();

        $obrasConSalidasIds = $salidas->pluck('salidaClientes')->flatten()
            ->pluck('obra_id') // 👈 usamos el ID directamente
            ->unique()
            ->filter();

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
            'cliente' => optional($obra->cliente)->empresa, // o nombre
        ]);

        $todasLasObras = $resources; // ✅ guarda el array completo aquí

        // 🔹 Festivos
        $festivos = $this->getFestivos();

        // 🔹 Fechas para el calendario
        $fechas = collect(range(0, 13))->map(fn($i) => [
            'fecha' => now()->addDays($i)->format('Y-m-d'),
            'dia' => now()->addDays($i)->locale('es')->translatedFormat('l')
        ]);

        // 🔹 Eventos
        $salidasEventos = $salidas->map(function ($salida) {
            $obra = optional($salida->salidaClientes->first())->obra;
            $empresa = optional($salida->empresaTransporte)->nombre;
            $pesoTotal = $salida->paquetes->sum(fn($paquete) => optional($paquete->planilla)->peso_total ?? 0);
            $pesoTotal = round($pesoTotal, 0);
            $fechaInicio = Carbon::parse($salida->fecha_salida);
            $fechaFin = $fechaInicio->copy()->addHours(3); // ⏱ +3 horas
            // 👇 Define color según estado
            $color = $salida->estado === 'completada' ? '#4CAF50' : '#3B82F6'; // verde o azul

            return [
                'title' => "{$salida->codigo_salida} - {$pesoTotal} kg",
                'id' => $salida->id,
                'start' => $fechaInicio->toDateTimeString(),
                'end' => $fechaFin->toDateTimeString(),
                'resourceId' => optional($obra)->id,
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

        $planillas = Planilla::with('obra')
            ->whereDoesntHave('paquetes.salidas') // Recogemos las planillas que no estan asociadas a una salida
            ->get();

        $eventosPlanillas = $planillas->map(function ($planilla) {
            $color = match ($planilla->estado) {
                'completada' => '#374151', // gris oscuro (Tailwind: gray-700)
                'fabricando' => '#6B7280', // gris medio (Tailwind: gray-500/600)
                'pendiente' => '#D1D5DB', // blanco/gris claro (Tailwind: gray-300)
                default => '#9E9E9E'       // gris neutro
            };

            // 👇 Primero parseamos la fecha correctamente
            // Como fecha_estimada_entrega ya es un Carbon, no hace falta parsear
            $fecha = Carbon::createFromFormat('d/m/Y H:i', $planilla->fecha_estimada_entrega);
            return [
                'title' => "{$planilla->codigo_limpio} ({$planilla->estado})",
                'id' => $planilla->id,
                'start' => $fecha->toDateTimeString(),
                'end' => $fecha->copy()->addHours(2)->toDateTimeString(),
                'resourceId' => $planilla->obra_id,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'tipo' => 'planilla',
                'extendedProps' => [
                    'tipo' => 'planilla'
                ]
            ];
        });

        $eventos = array_values(array_merge(
            $festivos,
            $salidasEventos->toArray(),
            $eventosPlanillas->toArray()
        ));

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
                'allDay' => true
            ],
            [
                'title' => 'Feria Los Palacios y Vfca',
                'start' => date('Y') . '-09-25',
                'backgroundColor' => '#ff0000',
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'allDay' => true
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
            Log::info('🛠 Actualizando planilla', [
                'id' => $id,
                'nueva_fecha_estimada' => $fecha->toDateString()
            ]);

            $planilla = Planilla::findOrFail($id);
            $planilla->fecha_estimada_entrega = $fecha->toDateString();
            $planilla->save();

            return response()->json(['success' => true, 'modelo' => 'planilla']);
        }

        return response()->json(['error' => 'Tipo no válido'], 400);
    }
}
