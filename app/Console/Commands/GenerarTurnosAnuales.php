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
        
        $this->info("✅ Turnos generados correctamente respetando la rotación individual de cada trabajador diurno.");
    }

    public function generarTurnos($user)
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

// Agregar esta lógica en UserObserver o en el modelo User
User::created(function ($user) {
    (new GenerarTurnosAnuales())->generarTurnos($user);
});
