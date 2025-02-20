<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AsignacionTurno;
use App\Models\Turno;
use Carbon\Carbon;

class ActualizarTurnos extends Command
{
    protected $signature = 'turnos:actualizar';
    protected $description = 'Asigna automáticamente los turnos rotativos a los trabajadores';

    public function handle()
    {
        $usuarios = AsignacionTurno::distinct()->pluck('user_id');
        $turnos = Turno::pluck('id')->toArray();

        foreach ($usuarios as $user_id) {
            $ultimoTurno = AsignacionTurno::where('user_id', $user_id)
                ->orderBy('fecha', 'desc')
                ->first();

            if ($ultimoTurno) {
                $proximoTurno = $this->obtenerProximoTurno($ultimoTurno->turno_id, $turnos);

                AsignacionTurno::create([
                    'user_id' => $user_id,
                    'turno_id' => $proximoTurno,
                    'fecha' => Carbon::tomorrow(),
                    'asignacion_manual' => false
                ]);
            }
        }

        $this->info('Turnos actualizados correctamente.');
    }

    private function obtenerProximoTurno($actualTurnoId, $turnos)
    {
        $indice = array_search($actualTurnoId, $turnos);
        return $turnos[($indice + 1) % count($turnos)];
    }
}
