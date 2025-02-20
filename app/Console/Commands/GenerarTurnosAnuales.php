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
    protected $description = 'Genera turnos para trabajadores diurnos y nocturnos durante todo el año excluyendo sábados y domingos';

    public function handle()
    {
        // Obtener los usuarios según su tipo de turno
        $diurnos = User::where('turno', 'diurno')->get();
        $nocturnos = User::where('turno', 'nocturno')->get();

        // Obtener los turnos por nombre
        $turnoMañana = Turno::where('nombre', 'mañana')->first()->id;
        $turnoTarde = Turno::where('nombre', 'tarde')->first()->id;
        $turnoNoche = Turno::where('nombre', 'noche')->first()->id;

        $inicio = Carbon::now()->startOfYear();
        $fin = Carbon::now()->endOfYear();
        
        $semanaMañana = true; // Alternador global para toda la empresa

        // **Asignar turnos a los trabajadores diurnos**
        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            // **Excluir sábados y domingos**
            if (in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                continue;
            }

            // Alternar semana de mañana y tarde
            $turnoAsignado = $semanaMañana ? $turnoMañana : $turnoTarde;

            // Aplicar a todos los trabajadores diurnos
            foreach ($diurnos as $user) {
                AsignacionTurno::firstOrCreate(
                    ['user_id' => $user->id, 'fecha' => $fecha->toDateString()],
                    ['turno_id' => $turnoAsignado, 'asignacion_manual' => false, 'modificado' => false]
                );
            }

            // **Cambiar el turno de toda la empresa al final de cada semana**
            if ($fecha->dayOfWeek == Carbon::FRIDAY) {
                $semanaMañana = !$semanaMañana;
            }
        }

        // **Asignar turnos a los trabajadores nocturnos**
        foreach ($nocturnos as $user) {
            for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
                // **Excluir sábados y domingos**
                if (in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                    continue;
                }

                // Siempre asigna turno nocturno
                AsignacionTurno::firstOrCreate(
                    ['user_id' => $user->id, 'fecha' => $fecha->toDateString()],
                    ['turno_id' => $turnoNoche, 'asignacion_manual' => false, 'modificado' => false]
                );
            }
        }

        $this->info("✅ Turnos generados correctamente para todo el año, alternando semanas para diurnos.");
    }
}
