<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class GenerarTurnosAnuales extends Command
{
    protected $signature = 'turnos:generar-anuales';
    protected $description = 'Genera turnos rotativos para trabajadores diurnos y turno fijo para nocturnos durante todo el año excluyendo sábados, domingos y festivos';

    public function handle()
    {
        $usuarios = User::all();

        // Obtener los festivos **antes** de generar turnos
        $festivos = $this->getFestivos();

        foreach ($usuarios as $user) {
            $this->generarTurnos($user, $festivos);
        }

        $this->info("✅ Turnos generados correctamente respetando la rotación individual de cada trabajador.");
    }

    public function generarTurnos($user, $festivos)
    {
        $turnoMañanaId = Turno::where('nombre', 'mañana')->value('id');
        $turnoTardeId = Turno::where('nombre', 'tarde')->value('id');
        $turnoNocheId = Turno::where('nombre', 'noche')->value('id');


        // Definir el inicio y fin del año actual
        $inicio = Carbon::now()->addDay()->startOfDay();
        $fin = Carbon::now()->endOfYear();

        if ($user->turno == 'diurno') {
            $turnoAsignado = ($user->turno_actual == 1) ? $turnoMañanaId : $turnoTardeId;
        } elseif ($user->turno == 'nocturno') {
            $turnoAsignado = $turnoNocheId;
        } elseif ($user->turno == 'mañana') {
            $turnoAsignado = $turnoMañanaId;
        } else {
            return; // No asignar turno si no es diurno o nocturno
        }

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            // **Excluye sábados, domingos y festivos**
            if (in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) || in_array($fecha->toDateString(), $festivos)) {
                continue;
            }

            AsignacionTurno::updateOrCreate(
                ['user_id' => $user->id, 'fecha' => $fecha->toDateString()],
                ['turno_id' => $turnoAsignado]
            );

            // Alternar turnos cada viernes para trabajadores diurnos
            if ($user->turno == 'diurno' && $fecha->dayOfWeek == Carbon::FRIDAY) {
                $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
            }
        }
    }

    private function getFestivos()
    {
        $response = Http::get("https://date.nager.at/api/v3/PublicHolidays/" . date('Y') . "/ES");

        if ($response->failed()) {
            return []; // Si la API falla, devolvemos un array vacío
        }

        return collect($response->json())->filter(function ($holiday) {
            // Si no tiene 'counties', es un festivo NACIONAL
            if (!isset($holiday['counties'])) {
                return true;
            }
            // Si el festivo pertenece a Andalucía
            return in_array('ES-AN', $holiday['counties']);
        })->pluck('date')->toArray(); // Extraer solo las fechas como array
    }
}
