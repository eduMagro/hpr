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
            'permisosAcceso',
            'tallas',
        ])->findOrFail($user->id);

        // Turnos disponibles para mostrarlos si hace falta
        $turnos = Turno::all();

        // Resumen de asistencias
        $resumen = $this->getResumenAsistencia($user);

        // Información de vacaciones
        $resumenVacaciones = $this->getResumenVacaciones($user);

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

        // Epis por firmar
        $episPorFirmar = \App\Models\EpiUsuario::where('user_id', $user->id)
            ->where('firmado', false)
            ->whereNull('devuelto_en')
            ->with('epi')
            ->get();

        return view('perfil.show', compact(
            'user',
            'turnos',
            'resumen',
            'resumenVacaciones',
            'horasMensuales',
            'config',
            'sesiones',
            'episPorFirmar'
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

    private function getResumenVacaciones(User $user): array
    {
        $añoActual = Carbon::now()->year;
        $fechaIncorporacion = $user->fecha_incorporacion_efectiva;

        // Días de vacaciones por convenio (por defecto 22)
        $diasPorAño = 22;

        // Calcular días que corresponden este año según fecha de incorporación
        if ($fechaIncorporacion && $fechaIncorporacion->year == $añoActual) {
            // Si se incorporó este año, calcular proporcionalmente
            $diasDelAño = Carbon::now()->isLeapYear() ? 366 : 365;
            $diasTrabajados = $fechaIncorporacion->diffInDays(Carbon::now()->endOfYear()) + 1;
            $diasCorresponden = (int) round(($diasTrabajados / $diasDelAño) * $diasPorAño);
        } else {
            $diasCorresponden = $diasPorAño;
        }

        // Días de vacaciones usados este año
        $diasUsados = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $añoActual)
            ->count();

        // Días restantes
        $diasRestantes = max(0, $diasCorresponden - $diasUsados);

        // Solicitudes de vacaciones pendientes
        $solicitudesPendientes = VacacionesSolicitud::where('user_id', $user->id)
            ->where('estado', 'pendiente')
            ->count();

        // Días en solicitudes pendientes (suma de días de todas las solicitudes pendientes)
        $diasEnSolicitudesPendientes = VacacionesSolicitud::where('user_id', $user->id)
            ->where('estado', 'pendiente')
            ->get()
            ->sum(function ($solicitud) {
                return Carbon::parse($solicitud->fecha_inicio)
                    ->diffInDays(Carbon::parse($solicitud->fecha_fin)) + 1;
            });

        return [
            'diasCorresponden' => $diasCorresponden,
            'diasUsados' => $diasUsados,
            'diasRestantes' => $diasRestantes,
            'solicitudesPendientes' => $solicitudesPendientes,
            'diasEnSolicitudesPendientes' => $diasEnSolicitudesPendientes,
            'añoActual' => $añoActual,
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
     * Parsea el User-Agent para mostrar información amigable del dispositivo
     */
    private function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [
                'navegador' => 'Navegador desconocido',
                'sistema' => 'Sistema desconocido',
                'dispositivo' => null,
                'icono' => 'desktop',
            ];
        }

        $agent = new \Jenssegers\Agent\Agent();
        $agent->setUserAgent($userAgent);

        // Detectar si es bot/crawler
        if ($agent->isRobot()) {
            return [
                'navegador' => $agent->robot() ?: 'Bot',
                'sistema' => 'Robot/Crawler',
                'dispositivo' => null,
                'icono' => 'bot',
            ];
        }

        // === NAVEGADOR ===
        $browser = $agent->browser();

        // Corregir detección incorrecta de "Webkit"
        if (!$browser || $browser === 'Webkit' || $browser === 'WebKit') {
            // Intentar detectar manualmente
            if (str_contains($userAgent, 'Edg/') || str_contains($userAgent, 'Edge/')) {
                $browser = 'Edge';
            } elseif (str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera')) {
                $browser = 'Opera';
            } elseif (str_contains($userAgent, 'Chrome/')) {
                $browser = 'Chrome';
            } elseif (str_contains($userAgent, 'Firefox/')) {
                $browser = 'Firefox';
            } elseif (str_contains($userAgent, 'Safari/')) {
                $browser = 'Safari';
            } else {
                $browser = 'Navegador';
            }
        }

        // Obtener versión del navegador
        $browserVersion = $agent->version($browser);
        if (!$browserVersion && preg_match('/' . preg_quote($browser, '/') . '[\/\s](\d+)/i', $userAgent, $matches)) {
            $browserVersion = $matches[1];
        }

        $navegador = $browser;
        if ($browserVersion) {
            $versionMajor = explode('.', (string) $browserVersion)[0];
            if (is_numeric($versionMajor) && $versionMajor < 200) { // Versión razonable
                $navegador .= ' ' . $versionMajor;
            }
        }

        // === SISTEMA OPERATIVO ===
        $platform = $agent->platform();

        // Corregir detección incorrecta
        if (!$platform || $platform === 'Webkit' || $platform === 'WebKit') {
            if (str_contains($userAgent, 'Windows')) {
                $platform = 'Windows';
            } elseif (str_contains($userAgent, 'Android')) {
                $platform = 'Android';
            } elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
                $platform = str_contains($userAgent, 'iPad') ? 'iPadOS' : 'iOS';
            } elseif (str_contains($userAgent, 'Mac OS X') || str_contains($userAgent, 'Macintosh')) {
                $platform = 'macOS';
            } elseif (str_contains($userAgent, 'Linux')) {
                $platform = 'Linux';
            } else {
                $platform = 'Sistema';
            }
        }

        // Renombrar para claridad
        if ($platform === 'AndroidOS') {
            $platform = 'Android';
        } elseif ($platform === 'OS X') {
            $platform = 'macOS';
        }

        $platformVersion = $agent->version($platform);
        $sistema = $platform;

        // Solo añadir versión si es razonable y no es Windows (donde la versión no es útil)
        if ($platformVersion && !str_contains($platform, 'Windows')) {
            $versionMajor = explode('.', (string) $platformVersion)[0];
            if (is_numeric($versionMajor) && $versionMajor < 50) {
                $sistema .= ' ' . $versionMajor;
            }
        }

        // === DISPOSITIVO ===
        $dispositivo = $agent->device();
        if ($dispositivo === 'Webkit' || $dispositivo === 'WebKit' || $dispositivo === false) {
            $dispositivo = null;
        }

        // === ICONO ===
        $icono = 'desktop';
        if ($agent->isPhone() || str_contains($userAgent, 'Mobile')) {
            $icono = 'mobile';
        } elseif ($agent->isTablet() || str_contains($userAgent, 'Tablet')) {
            $icono = 'tablet';
        }

        return [
            'navegador' => $navegador,
            'sistema' => $sistema,
            'dispositivo' => $dispositivo,
            'icono' => $icono,
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

        return back()->with('success', "Se han cerrado $cerradas sesión(es) en otros dispositivos.");
    }

    /**
     * Cerrar una sesión específica del usuario autenticado
     */
    public function cerrarSesion(string $sessionId)
    {
        $user = Auth::user();
        $sesionActualId = FacadeSession::getId();

        // No permitir cerrar la sesión actual
        if ($sessionId === $sesionActualId) {
            return back()->with('error', 'No puedes cerrar tu sesión actual.');
        }

        // Buscar y eliminar la sesión (solo si pertenece al usuario)
        $eliminada = Session::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        if ($eliminada) {
            return back()->with('success', 'Sesión cerrada correctamente.');
        }

        return back()->with('error', 'No se encontró la sesión.');
    }
}
