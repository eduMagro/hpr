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
use Illuminate\Support\Facades\Log;
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
                        $mq->whereIn('tipo', ['estribadora', 'cortadora_dobladora', 'grua']);
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
                        $mq->whereIn('tipo', ['estribadora', 'cortadora_dobladora', 'grua']);
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

        $eventosMaquinistas = $vacacionesMaquinistas->map(function ($asignacion) {
            return [
                'title' => $asignacion->user->name,
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171',
                'borderColor' => '#dc2626',
                'textColor' => 'white',
                'allDay' => true,
                'extendedProps' => [
                    'user_id' => $asignacion->user->id,
                ],
            ];
        })->toArray();

        $eventosFerrallas = $vacacionesFerrallas->map(function ($asignacion) {
            return [
                'title' => $asignacion->user->name,
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171',
                'borderColor' => '#dc2626',
                'textColor' => 'white',
                'allDay' => true,
                'extendedProps' => [
                    'user_id' => $asignacion->user->id,
                ],
            ];
        })->toArray();

        $eventosOficina = $vacacionesOficina->map(function ($asignacion) {
            return [
                'title' => $asignacion->user->name,
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171',
                'borderColor' => '#dc2626',
                'textColor' => 'white',
                'allDay' => true,
                'extendedProps' => [
                    'user_id' => $asignacion->user->id,
                ],
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
        $solicitud = VacacionesSolicitud::with('user')->findOrFail($id);
        $user = $solicitud->user;


        $solicitud->estado = 'aprobada';
        $solicitud->save();

        $rango = CarbonPeriod::create($solicitud->fecha_inicio, $solicitud->fecha_fin);
        $diasAsignados = 0;

        foreach ($rango as $fecha) {
            $fechaStr = $fecha->format('Y-m-d');

            if (in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                continue;
            }

            $asignacion = AsignacionTurno::firstOrNew([
                'user_id' => $user->id,
                'fecha'   => $fechaStr,
            ]);

            $estadoAnterior = $asignacion->estado;

            $asignacion->estado = 'vacaciones';
            $asignacion->maquina_id = $user->maquina_id;
            $asignacion->save();

            Log::info("✏️ Asignación actualizada para $fechaStr - estado anterior: " . ($estadoAnterior ?? 'ninguno'));

            if (is_null($estadoAnterior) || $estadoAnterior !== 'vacaciones') {
                $diasAsignados++;
            }
        }



        // Restar días de vacaciones disponibles
        if ($diasAsignados > 0) {
            $user->dias_vacaciones = max(0, $user->dias_vacaciones - $diasAsignados);
            $user->save();
        }
        Alerta::create([
            'user_id_1'      => auth()->id(), // Quien aprueba
            'destinatario_id' => $user->id,    // Quien recibe la alerta
            'mensaje'        => "Tus vacaciones del {$solicitud->fecha_inicio} al {$solicitud->fecha_fin} han sido aprobadas.",
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
        return redirect()->back()->with('success', "Solicitud aprobada. Se asignaron $diasAsignados días de vacaciones.");
    }

    public function denegar($id)
    {
        $solicitud = VacacionesSolicitud::with('user')->findOrFail($id);
        $user = $solicitud->user;

        $solicitud->estado = 'denegada';
        $solicitud->save();

        // 🛑 Alerta al trabajador
        Alerta::create([
            'user_id_1'       => auth()->id(), // quien deniega
            'destinatario_id' => $user->id,    // quien recibe
            'mensaje'         => "Tu solicitud de vacaciones del {$solicitud->fecha_inicio} al {$solicitud->fecha_fin} ha sido denegada.",
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return redirect()->back()->with('success', 'Solicitud denegada y alerta enviada.');
    }

    public function eliminarEvento(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha' => 'required|date',
        ]);

        $asignacion = AsignacionTurno::where('user_id', $validated['user_id'])
            ->where('fecha', $validated['fecha'])
            ->where('estado', 'vacaciones')
            ->first();

        if (!$asignacion) {
            return response()->json(['success' => false, 'error' => 'No se encontró la asignación de vacaciones.']);
        }

        // Cambiar el estado
        $asignacion->estado = 'activo';
        $asignacion->save();

        // Sumar un día al contador de vacaciones del usuario
        $usuario = User::find($validated['user_id']);
        if ($usuario) {
            $usuario->dias_vacaciones += 1;
            $usuario->save();
        }

        return response()->json(['success' => true]);
    }

    public function reprogramar(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha_original' => 'required|date',
            'nueva_fecha' => 'required|date',
        ]);

        $asignacion = AsignacionTurno::where('user_id', $validated['user_id'])
            ->where('fecha', $validated['fecha_original'])
            ->where('estado', 'vacaciones')
            ->first();

        if (!$asignacion) {
            return response()->json(['error' => 'Asignación no encontrada'], 404);
        }

        $asignacion->fecha = $validated['nueva_fecha'];
        $asignacion->save();

        return response()->json(['success' => true]);
    }
}
