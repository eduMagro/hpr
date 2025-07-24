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

class PerfilController extends Controller
{


    public function show(User $user)
    {
        // 🔒 Si quieres que solo pueda ver su propio perfil:
        if (Auth::id() !== $user->id) {
            abort(403, 'No tienes permiso para ver este perfil.');
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

        return view('perfil.show', compact(
            'user',
            'turnos',
            'resumen',
            'horasMensuales'
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
            'diasVacaciones'        => $conteos['vacaciones'] ?? 0,
            'faltasInjustificadas'  => $conteos['injustificada'] ?? 0,
            'faltasJustificadas'    => $conteos['justificada'] ?? 0,
            'diasBaja'              => $conteos['baja'] ?? 0,
        ];
    }
    private function getHorasMensuales(User $user): array
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $hoy       = Carbon::now()->toDateString();
        $finMes    = Carbon::now()->endOfMonth();

        // Todas las asignaciones activas del mes
        $asignacionesMes = $user->asignacionesTurnos()
            ->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->where('estado', 'activo')
            ->get();

        $horasTrabajadas     = 0;
        $diasConErrores      = 0;
        $diasHastaHoy        = 0;
        $totalAsignacionesMes = $asignacionesMes->count(); // todas las asignaciones activas del mes

        foreach ($asignacionesMes as $asignacion) {
            // Solo para horas hasta hoy
            if ($asignacion->fecha <= $hoy) {
                $diasHastaHoy++;
            }

            $horaEntrada = $asignacion->entrada ? Carbon::parse($asignacion->entrada) : null;
            $horaSalida  = $asignacion->salida  ? Carbon::parse($asignacion->salida)  : null;

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
            'horas_trabajadas'       => $horasTrabajadas,
            'horas_deberia_llevar'   => $horasDeberiaLlevar,
            'dias_con_errores'       => $diasConErrores,
            'horas_planificadas_mes' => $horasPlanificadasMes,
        ];
    }
}
