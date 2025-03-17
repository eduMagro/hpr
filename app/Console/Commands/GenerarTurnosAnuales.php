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
        $turnoVacacionesId = Turno::where('nombre', 'vacaciones')->value('id'); // Obtener dinámicamente el ID de vacaciones

        // Definir el inicio y fin del año actual
        $inicio = Carbon::now()->addDay()->startOfDay();
        $fin = Carbon::now()->endOfYear();

        // Obtener festivos desde el método getFestivos()
        $festivos = $this->getFestivos();
        $festivosArray = collect($festivos)->pluck('start')->toArray();
        // Obtener los días donde ya hay una asignación de vacaciones (turno_id = 10)
        $diasVacaciones = AsignacionTurno::where('user_id', $user->id)
            ->where('turno_id', $turnoVacacionesId)
            ->pluck('fecha')
            ->toArray();

        if ($user->turno == 'nocturno') {
            $turnoAsignado = $turnoNocheId;
        } elseif ($user->turno == 'mañana') {
            $turnoAsignado = $turnoMañanaId;
        } else {
            return; // No asignar turno si no es diurno o nocturno
        }

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
                // Guardar el turno del viernes antes de saltarlo si es festivo o vacaciones
                $esViernes = $fecha->dayOfWeek == Carbon::FRIDAY;

                // Excluir sábados, domingos, festivos y días con turno de vacaciones
                if (
                    in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) ||
                    in_array($fecha->toDateString(), $festivosArray) ||
                    in_array($fecha->toDateString(), $diasVacaciones)
                ) {
                    // Si es viernes, cambiar turno aunque no se registre en la base de datos
                    if ($user->turno == 'diurno' && $esViernes) {
                        $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                    }
                    continue;
                }

                // Registrar el turno en la base de datos
                AsignacionTurno::updateOrCreate(
                    ['user_id' => $user->id, 'fecha' => $fecha->toDateString()],
                    ['turno_id' => $turnoAsignado]
                );

                // Si es viernes, cambiar turno para la próxima semana
                if ($user->turno == 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                }
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
