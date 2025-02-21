<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vacaciones;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class VacacionesController extends Controller
{

    public function index()
    {
        //----------------- VACACIONES
        $vacaciones = Vacaciones::with('user')->get();

        $eventosVacaciones = $vacaciones->map(function ($vacacion) {
            return [
                'title' => 'Vacaciones: ' . $vacacion->user->name,
                'start' => Carbon::parse($vacacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171',
                'borderColor' => '#dc2626',
                'textColor' => 'white',
                'allDay' => true
            ];
        })->toArray(); // Convertimos la colección a array

        //----------------- OBTENER FESTIVOS
        $response = Http::get("https://date.nager.at/api/v3/PublicHolidays/" . date('Y') . "/ES");

        $festivos = [];

        if ($response->successful()) {
            $festivos = collect($response->json())->filter(function ($holiday) {
                // Si no tiene 'counties', es un festivo NACIONAL
                if (!isset($holiday['counties'])) {
                    return true;
                }

                // Si el festivo pertenece a Andalucía
                return in_array('ES-AN', $holiday['counties']);
            })->map(function ($holiday) {
                return [
                    'title' => $holiday['localName'],
                    'start' => $holiday['date'],
                    'backgroundColor' => '#ff0000', // Rojo para festivos
                    'borderColor' => '#b91c1c',
                    'textColor' => 'white',
                    'allDay' => true
                ];
            })->toArray();
        }

        // Mezclar festivos y vacaciones de empleados
        $eventosVacaciones = array_merge($festivos, $eventosVacaciones);

        return view('vacaciones.index', compact('eventosVacaciones'));
    }



    public function store(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'fecha' => 'required|date',
            ]);

            $user = User::findOrFail($request->user_id);

            // Verificar si el usuario tiene días de vacaciones disponibles
            if ($user->dias_vacaciones <= 0) {
                return response()->json(['error' => 'No tiene más días de vacaciones disponibles.'], 400);
            }

            // Verificar si ya ha seleccionado ese día
            if (Vacaciones::where('user_id', $user->id)->where('fecha', $request->fecha)->exists()) {
                return response()->json(['error' => 'Ese día ya ha sido seleccionado como vacaciones.'], 400);
            }

            // Registrar el día de vacaciones
            Vacaciones::create([
                'user_id' => $user->id,
                'fecha' => $request->fecha,
            ]);

            // Restar un día de vacaciones
            $user->decrement('dias_vacaciones');

            return response()->json(['success' => 'Día de vacaciones registrado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar las vacaciones: ' . $e->getMessage()], 500);
        }
    }
}
