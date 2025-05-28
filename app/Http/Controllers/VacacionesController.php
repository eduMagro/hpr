<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AsignacionTurno;
use App\Models\Festivo;
use App\Models\User;
use App\Models\VacacionesSolicitud;
use App\Models\Alerta;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;

class VacacionesController extends Controller
{

    public function index()
    {

        // 🟡 Solicitudes de maquinistas
        $solicitudesMaquinistas = VacacionesSolicitud::with('user.maquina')
            ->where('estado', 'pendiente')
            ->whereHas('user', function ($q) {
                $q->where('rol', 'operario')
                    ->whereHas('maquina', function ($mq) {
                        $mq->whereIn('tipo', ['estribadora', 'cortadora_dobladora']);
                    });
            })
            ->orderBy('fecha_inicio')
            ->get();

        // 🟠 Solicitudes de ferrallas
        $solicitudesFerrallas = VacacionesSolicitud::with('user.maquina')
            ->where('estado', 'pendiente')
            ->whereHas('user', function ($q) {
                $q->where('rol', 'operario')
                    ->where(function ($inner) {
                        $inner->whereNull('maquina_id')
                            ->orWhereHas('maquina', fn($mq) => $mq->where('tipo', 'ensambladora'));
                    });
            })
            ->orderBy('fecha_inicio')
            ->get();

        // 🔵 Solicitudes de oficina
        $solicitudesOficina = VacacionesSolicitud::with('user')
            ->where('estado', 'pendiente')
            ->whereHas('user', fn($q) => $q->where('rol', 'oficina'))
            ->orderBy('fecha_inicio')
            ->get();
        $totalSolicitudesPendientes = $solicitudesMaquinistas->count() +
            $solicitudesFerrallas->count() +
            $solicitudesOficina->count();

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
            'eventosOficina' => $eventosOficina,
            'solicitudesMaquinistas' => $solicitudesMaquinistas,
            'solicitudesFerrallas' => $solicitudesFerrallas,
            'solicitudesOficina' => $solicitudesOficina,
            'totalSolicitudesPendientes' => $totalSolicitudesPendientes,
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
        ]);

        VacacionesSolicitud::create([
            'user_id' => auth()->id(),
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'estado' => 'pendiente'
        ]);
        $rrhh = User::where('email', 'josemanuel.amuedo@pacoreyes.com')->first();
        if ($rrhh) {
            Alerta::create([
                'user_id_1' => auth()->id(),
                'destinatario_id' => $rrhh->id,
                'mensaje' => auth()->user()->name . ' ha solicitado vacaciones del ' .
                    $request->fecha_inicio . ' al ' . $request->fecha_fin,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        return response()->json(['success' => 'Solicitud registrada correctamente.']);
    }
    public function aprobar($id)
    {
        $solicitud = VacacionesSolicitud::findOrFail($id);
        $solicitud->estado = 'aprobada';
        $solicitud->save();

        // Crear registros en asignaciones_turnos
        $rango = CarbonPeriod::create($solicitud->fecha_inicio, $solicitud->fecha_fin);

        foreach ($rango as $fecha) {
            AsignacionTurno::updateOrCreate(
                ['user_id' => $solicitud->user_id, 'fecha' => $fecha->format('Y-m-d')],
                ['estado' => 'vacaciones']
            );
        }

        return redirect()->back()->with('success', 'Solicitud aprobada y vacaciones asignadas.');
    }

    public function denegar($id)
    {
        $solicitud = VacacionesSolicitud::findOrFail($id);
        $solicitud->estado = 'denegada';
        $solicitud->save();

        return redirect()->back()->with('success', 'Solicitud denegada.');
    }
}
