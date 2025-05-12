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
        // Vacaciones de operarios
        $vacacionesOperarios = AsignacionTurno::with('user', 'turno')
            ->whereHas('turno', fn($q) => $q->where('nombre', 'vacaciones'))
            ->whereHas('user', fn($q) => $q->where('rol', 'operario'))
            ->get();

        // Vacaciones de oficina
        $vacacionesOficina = AsignacionTurno::with('user', 'turno')
            ->whereHas('turno', fn($q) => $q->where('nombre', 'vacaciones'))
            ->whereHas('user', fn($q) => $q->where('rol', 'oficina'))
            ->get();

        // Festivos comunes
        $festivos = Festivo::select('fecha', 'titulo')->get()->map(function ($festivo) {
            return [
                'id' => $festivo->id,
                'title' => $festivo->titulo,
                'start' => $festivo->fecha,
                'backgroundColor' => '#ff2800', // Rojo Ferrari
                'borderColor' => '#b22222',
                'textColor' => 'white',
                'allDay' => true,
                'editable' => true
            ];
        })->toArray();

        // Eventos para operarios
        $eventosOperarios = $vacacionesOperarios->map(function ($asignacion) {
            return [
                'title' => $asignacion->user->name,
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171', // Rosa claro
                'borderColor' => '#dc2626',     // Rojo oscuro
                'textColor' => 'white',
                'allDay' => true
            ];
        })->toArray();

        // Eventos para oficina
        $eventosOficina = $vacacionesOficina->map(function ($asignacion) {
            return [
                'title' => $asignacion->user->name,
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171', // Rosa claro
                'borderColor' => '#dc2626',     // Rojo oscuro
                'textColor' => 'white',
                'allDay' => true
            ];
        })->toArray();

        // Añadir los festivos a ambos calendarios
        $eventosOperarios = array_merge($eventosOperarios, $festivos);
        $eventosOficina = array_merge($eventosOficina, $festivos);

        return view('vacaciones.index', [
            'eventosOperarios' => $eventosOperarios,
            'eventosOficina' => $eventosOficina
        ]);
    }
}
