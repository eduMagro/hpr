<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;
use App\Models\Salida;
use App\Models\Obra;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class PlanificacionController extends Controller
{
    public function index()
    {
        // 🔹 Definir rango de fechas para filtrar las salidas
        $rangoFechas = [Carbon::now()->startOfDay(), Carbon::now()->addDays(7)->endOfDay()];
        $rangoFechas = [
            $rangoFechas[0]->toDateTimeString(),
            $rangoFechas[1]->toDateTimeString()
        ];

        // 🔹 Obtener obras con salidas dentro del rango de fechas
        $obrasConSalidas = Obra::whereHas('salidaClientes.salida', function ($query) use ($rangoFechas) {
            $query->whereBetween('fecha_salida', $rangoFechas);
        })->get();

        // 🔹 Obtener las salidas con relaciones necesarias
        $salidas = Salida::with([
            'salidaClientes.obra:id,obra',
            'salidaClientes.cliente:id,empresa',
            'paquetes.planilla.user'
        ])
            ->whereBetween('fecha_salida', $rangoFechas)
            ->get();

        // 🔹 Obtener los festivos
        $festivos = $this->getFestivos();

        // 🔹 Generar las fechas para el calendario
        $fechas = collect(range(0, 13))->map(fn($i) => [
            'fecha' => now()->addDays($i)->format('Y-m-d'),
            'dia' => now()->addDays($i)->locale('es')->translatedFormat('l')
        ]);

        // 🔹 Convertir cada salida en un evento con el cálculo del peso total optimizado
        $salidasEventos = $salidas->map(function ($salida) {
            $obra = optional($salida->salidaClientes->first())->obra;

            $pesoTotal = $salida->paquetes->sum(fn($paquete) => optional($paquete->planilla)->peso_total ?? 0);
            $pesoTotal = round($pesoTotal, 0);

            return [
                'title' => "{$salida->codigo_salida} - {$pesoTotal} kg",
                'start' => $salida->fecha_salida,
                'resourceId' => optional($obra)->id,
            ];
        });

        // 🔹 Fusionar eventos fijos + festivos + salidas
        $eventos = array_values(array_merge($festivos, $salidasEventos->toArray()));

        return view('planificacion.index', compact('fechas', 'eventos', 'obrasConSalidas'));
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
                'allDay' => true
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
}
