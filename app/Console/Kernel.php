<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define el schedule de comandos.
     */
    protected function schedule(Schedule $schedule)
    {
        // Verificar fichajes de entrada 30 minutos después del inicio de cada turno
        // Turno mañana (06:00) → verificar a las 06:30
        $schedule->command('fichajes:verificar-entradas --turno=mañana')
            ->dailyAt('06:30')
            ->withoutOverlapping()
            ->runInBackground();

        // Turno tarde (14:00) → verificar a las 14:30
        $schedule->command('fichajes:verificar-entradas --turno=tarde')
            ->dailyAt('14:30')
            ->withoutOverlapping()
            ->runInBackground();

        // Turno noche (22:00) → verificar a las 22:30
        $schedule->command('fichajes:verificar-entradas --turno=noche')
            ->dailyAt('22:30')
            ->withoutOverlapping()
            ->runInBackground();
    }
}
