<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use Carbon\Carbon;

class GenerarTurnosAnuales extends Command
{
    protected $signature = 'turnos:generar-anuales';
    protected $description = 'Genera turnos rotativos para trabajadores diurnos y turno fijo para nocturnos durante todo el año excluyendo sábados y domingos';

    public function handle()
    {
        $usuarios = User::all();
        foreach ($usuarios as $user) {
            $this->generarTurnos($user);
        }
        
        $this->info("✅ Turnos generados correctamente respetando la rotación individual de cada trabajador.");
    }

    public function generarTurnos($user)
    {
        $turnoMañanaId = Turno::where('nombre', 'mañana')->value('id');
        $turnoTardeId = Turno::where('nombre', 'tarde')->value('id');
        $turnoNocheId = Turno::where('nombre', 'noche')->value('id');

        $inicio = Carbon::now()->startOfYear();
        $fin = Carbon::now()->endOfYear();
        
        if ($user->turno == 'diurno') {
            $turnoAsignado = ($user->turno_actual == $turnoMañanaId) ? $turnoMañanaId : $turnoTardeId;
        } else {
            $turnoAsignado = $turnoNocheId;
        }
        
        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            if (in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                continue;
            }

            AsignacionTurno::firstOrCreate(
                ['user_id' => $user->id, 'fecha' => $fecha->toDateString()],
                ['turno_id' => $turnoAsignado, 'asignacion_manual' => false, 'modificado' => false]
            );

            if ($user->turno == 'diurno' && $fecha->dayOfWeek == Carbon::FRIDAY) {
                $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
            }
        }
    }
}
