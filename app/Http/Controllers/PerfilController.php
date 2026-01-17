<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use App\Models\VacacionesSolicitud;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session as FacadeSession;
use App\Models\Session;

class PerfilController extends Controller
{


    public function show(User $user)
    {
        // 🔒 Si quieres que solo pueda ver su propio perfil:
        if (Auth::id() !== $user->id) {
            return back()->with('error', 'No tienes permiso para ver este perfil.');
        }

        // Recarga el usuario con todas sus relaciones necesarias
        $user = User::with([
            'empresa',
            'categoria',
            'convenio',
            'maquina',
            'departamentos',
            'alertasLeidas',
            'asignacionesTurnos.turno',
            'entradas',
            'salidas',
            'movimientos',
            'elementos1',
            'elementos2',
            'etiquetasComoSoldador1',
            'etiquetasComoSoldador2',
            'etiquetasComoEnsamblador1',
            'etiquetasComoEnsamblador2',
            'permisosAcceso',
        ])->findOrFail($user->id);

        // Turnos disponibles para mostrarlos si hace falta
        $turnos = Turno::all();

        // Resumen de asistencias
        $resumen = $this->getResumenAsistencia($user);

        // Horas trabajadas del mes
        $horasMensuales = $this->getHorasMensuales($user);

        // Configuración del calendario (para fichajes y visualización)
        $esOficina = Auth::user()->rol === 'oficina';
        $config = [
            'userId' => $user->id,
            'locale' => 'es',
            'csrfToken' => csrf_token(),
            'routes' => [
                'eventosUrl' => route('users.verEventos-turnos', $user->id),
                'resumenUrl' => route('users.verResumen-asistencia', ['user' => $user->id]),
                'vacacionesStoreUrl' => route('vacaciones.solicitar'),
                'storeUrl' => route('asignaciones-turnos.store'),
                'destroyUrl' => route('asignaciones-turnos.destroy'),
                'vacationDataUrl' => route('usuarios.getVacationData', ['user' => $user->id]),
                'misSolicitudesPendientesUrl' => route('vacaciones.verMisSolicitudesPendientes'),
                'eliminarSolicitudUrl' => url('/vacaciones/solicitud'),
                'eliminarDiasSolicitudUrl' => route('vacaciones.eliminarDiasSolicitud'),
            ],
            'enableListMonth' => true,
            'mobileBreakpoint' => 768,
            'permissions' => [
                // Todos pueden solicitar vacaciones desde su perfil
                'canRequestVacations' => true,
                'canEditHours' => false,
                'canAssignShifts' => false,
                'canAssignStates' => false,
            ],
            'turnos' => $turnos->map(fn($t) => ['nombre' => $t->nombre])->values()->toArray(),
            'fechaIncorporacion' => $user->fecha_incorporacion_efectiva ? $user->fecha_incorporacion_efectiva->format('Y-m-d') : null,
            'diasVacacionesAsignados' => $user->asignacionesTurnos()
                ->where('estado', 'vacaciones')
                ->count(),
        ];

        // Sesiones activas del usuario
        $sesiones = Session::where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($sesion) {
                $lastActivity = Carbon::createFromTimestamp($sesion->last_activity);
                return [
                    'id' => $sesion->id,
                    'ip_address' => $sesion->ip_address,
                    'user_agent' => $sesion->user_agent,
                    'dispositivo' => $this->parseUserAgent($sesion->user_agent),
                    'ultima_actividad' => $lastActivity->format('d/m/Y H:i'),
                    'tiempo_relativo' => $lastActivity->diffForHumans(),
                    'actual' => $sesion->id === FacadeSession::getId(),
                ];
            });

        return view('perfil.show', compact(
            'user',
            'turnos',
            'resumen',
            'horasMensuales',
            'config',
            'sesiones'
        ));
    }
    private function getResumenAsistencia(User $user): array
    {
        $inicioAño = Carbon::now()->startOfYear();

        $conteos = AsignacionTurno::select('estado', DB::raw('count(*) as total'))
            ->where('user_id', $user->id)
            ->where('fecha', '>=', $inicioAño)
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return [
            'diasVacaciones' => $conteos['vacaciones'] ?? 0,
            'faltasInjustificadas' => $conteos['injustificada'] ?? 0,
            'faltasJustificadas' => $conteos['justificada'] ?? 0,
            'diasBaja' => $conteos['baja'] ?? 0,
        ];
    }
    private function getHorasMensuales(User $user): array
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $hoy = Carbon::now()->toDateString();
        $finMes = Carbon::now()->endOfMonth();

        // Todas las asignaciones activas del mes
        $asignacionesMes = $user->asignacionesTurnos()
            ->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->where('estado', 'activo')
            ->get();

        $horasTrabajadas = 0;
        $diasConErrores = 0;
        $diasHastaHoy = 0;
        $totalAsignacionesMes = $asignacionesMes->count(); // todas las asignaciones activas del mes

        foreach ($asignacionesMes as $asignacion) {
            // Solo para horas hasta hoy
            if ($asignacion->fecha <= $hoy) {
                $diasHastaHoy++;
            }

            $horaEntrada = $asignacion->entrada ? Carbon::parse($asignacion->entrada) : null;
            $horaSalida = $asignacion->salida ? Carbon::parse($asignacion->salida) : null;

            if ($horaEntrada && $horaSalida) {
                $horasDia = $horaSalida->diffInMinutes($horaEntrada) / 60;
                if ($horasDia < 8) {
                    $horasDia = 8;
                }
                $horasTrabajadas += $horasDia;
            } else {
                // 👉 Sólo contar error si la fecha ya pasó o es hoy
                if ($asignacion->fecha < $hoy) {
                    $diasConErrores++;
                }
            }
        }

        // Horas que debería llevar hasta hoy
        $horasDeberiaLlevar = ($diasHastaHoy) * 8;

        // Horas planificadas en el mes completo (todas las asignaciones activas × 8)
        $horasPlanificadasMes = $totalAsignacionesMes * 8;

        return [
            'horas_trabajadas' => $horasTrabajadas,
            'horas_deberia_llevar' => $horasDeberiaLlevar,
            'dias_con_errores' => $diasConErrores,
            'horas_planificadas_mes' => $horasPlanificadasMes,
        ];
    }

    /**
     * Parsea el User-Agent usando jenssegers/agent para mejor detección
     */
    private function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [
                'navegador' => 'Desconocido',
                'sistema' => 'Desconocido',
                'dispositivo' => 'Desconocido',
                'icono' => 'desktop',
                'es_bot' => false,
            ];
        }

        $agent = new \Jenssegers\Agent\Agent();
        $agent->setUserAgent($userAgent);

        // Detectar si es bot/crawler
        if ($agent->isRobot()) {
            return [
                'navegador' => $agent->robot() ?: 'Bot',
                'sistema' => 'Robot/Crawler',
                'dispositivo' => 'Bot',
                'icono' => 'bot',
                'es_bot' => true,
            ];
        }

        // Navegador y versión
        $browser = $agent->browser();
        $browserVersion = $agent->version($browser);
        $navegador = $browser ?: 'Desconocido';
        if ($browserVersion) {
            $navegador .= ' ' . explode('.', $browserVersion)[0]; // Solo versión mayor
        }

        // Sistema operativo y versión
        $platform = $agent->platform();
        $platformVersion = $agent->version($platform);
        $sistema = $platform ?: 'Desconocido';
        if ($platformVersion && $platform !== 'Windows') {
            $sistema .= ' ' . explode('.', $platformVersion)[0];
        }

        // Dispositivo específico
        $dispositivo = $agent->device() ?: null;

        // Tipo de icono
        $icono = 'desktop';
        if ($agent->isPhone()) {
            $icono = 'mobile';
        } elseif ($agent->isTablet()) {
            $icono = 'tablet';
        }

        return [
            'navegador' => $navegador,
            'sistema' => $sistema,
            'dispositivo' => $dispositivo,
            'icono' => $icono,
            'es_bot' => false,
        ];
    }

    /**
     * Cerrar todas las sesiones del usuario autenticado (excepto la actual)
     */
    public function cerrarMisSesiones()
    {
        $user = Auth::user();
        $sesionActualId = FacadeSession::getId();

        // Eliminar todas las sesiones excepto la actual
        $cerradas = Session::where('user_id', $user->id)
            ->where('id', '!=', $sesionActualId)
            ->delete();

        return back()->with('success', "🛑 Se han cerrado $cerradas sesión(es) en otros dispositivos.");
    }
}
