<?php
namespace App\Observers;

use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use Carbon\Carbon;

class UserObserver
{
    public function created(User $user)
    {
        $this->generarTurnos($user);
    }

    private function generarTurnos(User $user)
    {
        $turnoMañana = Turno::where('nombre', 'mañana')->first()->id;
        $turnoTarde = Turno::where('nombre', 'tarde')->first()->id;
        $turnoNoche = Turno::where('nombre', 'noche')->first()->id;

        $inicio = Carbon::now()->startOfYear();
        $fin = Carbon::now()->endOfYear();

        $turnoAsignado = ($user->turno == 'diurno')
            ? ($user->turno_actual === 'mañana' ? $turnoMañana : $turnoTarde)
            : $turnoNoche;

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            if (in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                continue;
            }

            AsignacionTurno::firstOrCreate(
                ['user_id' => $user->id, 'fecha' => $fecha->toDateString()],
                ['turno_id' => $turnoAsignado, 'asignacion_manual' => false, 'modificado' => false]
            );

            if ($user->turno == 'diurno' && $fecha->dayOfWeek == Carbon::FRIDAY) {
                $turnoAsignado = ($turnoAsignado === $turnoMañana) ? $turnoTarde : $turnoMañana;
            }
        }
    }
}
