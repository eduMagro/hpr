<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vacaciones;
use App\Models\User;
use Carbon\Carbon;

class VacacionesController extends Controller
{

    public function index()
    {
        $vacaciones = Vacaciones::with('user')->get();

        $eventosVacaciones = $vacaciones->map(function ($vacacion) {
            return [
                'title' => 'Vacaciones: ' . $vacacion->user->name, // Mostrar el nombre del trabajador
                'start' => \Carbon\Carbon::parse($vacacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171', // Rojo claro para vacaciones
                'borderColor' => '#dc2626', // Rojo oscuro para el borde
                'textColor' => 'white',
                'allDay' => true
            ];
        });

        return view('vacaciones.index', [
            'eventosVacaciones' => json_encode($eventosVacaciones, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ]);
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
