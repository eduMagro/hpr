<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AsignacionTurno;
use App\Models\Festivo;
use App\Models\User;
use App\Models\VacacionesSolicitud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class VacacionesController extends Controller
{

    public function index()
    {
        // Vacaciones de maquinistas
        $vacacionesMaquinistas = AsignacionTurno::with(['user.maquina', 'turno'])
            ->where('estado', 'vacaciones')
            ->whereHas('user', function ($q) {
                $q->where('rol', 'operario')
                    ->whereHas('maquina', function ($mq) {
                        $mq->whereIn('tipo', ['estribadora', 'cortadora_dobladora']);
                    });
            })
            ->get();

        // Vacaciones de ferrallas
        $vacacionesFerrallas = AsignacionTurno::with(['user.maquina', 'turno'])
            ->where('estado', 'vacaciones')
            ->whereHas('user', function ($q) {
                $q->where('rol', 'operario')
                    ->where(function ($inner) {
                        $inner->whereNull('maquina_id')
                            ->orWhereHas('maquina', fn($mq) => $mq->where('tipo', 'ensambladora'));
                    });
            })
            ->get();

        // Vacaciones de oficina
        $vacacionesOficina = AsignacionTurno::with('user', 'turno')
            ->where('estado', 'vacaciones')
            ->whereHas('user', fn($q) => $q->where('rol', 'oficina'))
            ->get();

        // Festivos comunes
        $festivos = Festivo::select('fecha', 'titulo')->get()->map(function ($festivo) {
            return [
                'id' => $festivo->id,
                'title' => $festivo->titulo,
                'start' => $festivo->fecha,
                'backgroundColor' => '#ff2800',
                'borderColor' => '#b22222',
                'textColor' => 'white',
                'allDay' => true,
                'editable' => true
            ];
        })->toArray();

        // Eventos visuales
        $eventosMaquinistas = $vacacionesMaquinistas->map(function ($asignacion) {
            return [
                'title' => $asignacion->user->name,
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171',
                'borderColor' => '#dc2626',
                'textColor' => 'white',
                'allDay' => true
            ];
        })->toArray();

        $eventosFerrallas = $vacacionesFerrallas->map(function ($asignacion) {
            return [
                'title' => $asignacion->user->name,
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171',
                'borderColor' => '#dc2626',
                'textColor' => 'white',
                'allDay' => true
            ];
        })->toArray();

        $eventosOficina = $vacacionesOficina->map(function ($asignacion) {
            return [
                'title' => $asignacion->user->name,
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171',
                'borderColor' => '#dc2626',
                'textColor' => 'white',
                'allDay' => true
            ];
        })->toArray();

        // Añadir festivos a cada grupo
        $eventosMaquinistas = array_merge($eventosMaquinistas, $festivos);
        $eventosFerrallas = array_merge($eventosFerrallas, $festivos);
        $eventosOficina = array_merge($eventosOficina, $festivos);

        return view('vacaciones.index', [
            'eventosMaquinistas' => $eventosMaquinistas,
            'eventosFerrallas' => $eventosFerrallas,
            'eventosOficina' => $eventosOficina
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'motivo' => 'nullable|string'
        ]);

        VacacionesSolicitud::create([
            'user_id' => auth()->id(),
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'motivo' => $request->motivo,
            'estado' => 'pendiente'
        ]);

        return response()->json(['success' => 'Solicitud registrada correctamente.']);
    }
}
