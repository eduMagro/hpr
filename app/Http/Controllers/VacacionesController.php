<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AsignacionTurno;
use App\Models\Festivo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class VacacionesController extends Controller
{

    public function index()
    {
        //----------------- OBTENER VACACIONES DESDE ASIGNACIONES_TURNOS
        $vacaciones = AsignacionTurno::with('user', 'turno')
            ->whereHas('turno', function ($query) {
                $query->where('nombre', 'vacaciones'); // Filtrar solo los turnos de vacaciones
            })
            ->get();

        $eventosVacaciones = $vacaciones->map(function ($asignacion) {
            return [
                'title' => $asignacion->user->name,
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171', // Rojo claro para vacaciones
                'borderColor' => '#dc2626', // Rojo oscuro para el borde
                'textColor' => 'white',
                'allDay' => true
            ];
        })->toArray(); // Convertimos la colección a array

        //----------------- OBTENER FESTIVOS
        $festivos = Festivo::select('fecha', 'titulo')->get()->map(function ($festivo) {
            return [
                'id' => $festivo->id,
                'title' => $festivo->titulo,
                'start' => $festivo->fecha,
                'backgroundColor' => '#ff2800', // Rojo Ferrari
                'borderColor' => '#b22222',
                'textColor' => 'white',
                'allDay' => true,
                'editable' => true // ✅ Esto permite moverlo
            ];
        })->toArray();


        // Mezclar festivos y vacaciones de empleados
        $eventosVacaciones = array_merge($festivos, $eventosVacaciones);


        return view('vacaciones.index', compact('eventosVacaciones'));
    }


    public function moverFestivo(Request $request)
    {
        $festivo = Festivo::findOrFail($request->id);
        $festivo->fecha = $request->nueva_fecha;
        $festivo->save();

        return response()->json(['mensaje' => 'Festivo actualizado']);
    }

    // public function store(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'user_id' => 'required|exists:users,id',
    //             'fecha' => 'required|date',
    //         ]);

    //         $user = User::findOrFail($request->user_id);

    //         // Verificar si el usuario tiene días de vacaciones disponibles
    //         if ($user->dias_vacaciones <= 0) {
    //             return response()->json(['error' => 'No tiene más días de vacaciones disponibles.'], 400);
    //         }

    //         // Verificar si ya ha seleccionado ese día
    //         if (Vacaciones::where('user_id', $user->id)->where('fecha', $request->fecha)->exists()) {
    //             return response()->json(['error' => 'Ese día ya ha sido seleccionado como vacaciones.'], 400);
    //         }

    //         // Registrar el día de vacaciones
    //         Vacaciones::create([
    //             'user_id' => $user->id,
    //             'fecha' => $request->fecha,
    //         ]);

    //         // Restar un día de vacaciones
    //         $user->decrement('dias_vacaciones');

    //         return response()->json(['success' => 'Día de vacaciones registrado correctamente.']);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Error al registrar las vacaciones: ' . $e->getMessage()], 500);
    //     }
    // }
}
