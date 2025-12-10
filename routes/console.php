<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

$schedule = app(Schedule::class);

// =====================================================================
// BACKUP DE BASE DE DATOS
// =====================================================================

// Backup horario de la base de datos (cada hora en punto, retención 24h)
$schedule->command('backup:database --hourly')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->timezone('Europe/Madrid')
    ->appendOutputTo(storage_path('logs/backup-hourly.log'));

// Backup diario completo (a las 02:00, retención 7 días)
$schedule->command('backup:database --daily')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->timezone('Europe/Madrid')
    ->appendOutputTo(storage_path('logs/backup-daily.log'));

// =====================================================================
// VERIFICACIÓN DE FICHAJES
// =====================================================================

// Verificar fichajes de entrada 30 minutos después del inicio de cada turno
// Turno mañana (06:00) → verificar a las 06:30
$schedule->command('fichajes:verificar-entradas --turno=mañana')
    ->dailyAt('06:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->timezone('Europe/Madrid');

// Turno tarde (14:00) → verificar a las 14:30
$schedule->command('fichajes:verificar-entradas --turno=tarde')
    ->dailyAt('14:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->timezone('Europe/Madrid');

// Turno noche (22:00) → verificar a las 22:30
$schedule->command('fichajes:verificar-entradas --turno=noche')
    ->dailyAt('22:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->timezone('Europe/Madrid');

// =====================================================================
// TAREAS ANUALES
// =====================================================================

// Generar turnos para el nuevo año (1 de enero a medianoche)
$schedule->command('turnos:generar-anuales')
    ->yearlyOn(1, 1, '00:00')
    ->timezone('Europe/Madrid');

// Resetear días de vacaciones (1 de enero a medianoche)
$schedule->command('vacaciones:reset')
    ->yearlyOn(1, 1, '00:00')
    ->timezone('Europe/Madrid');

// Sincronizar festivos del año nuevo (1 de enero a las 01:10)
$schedule->command('festivos:sincronizar')
    ->yearlyOn(1, 1, '01:10')
    ->timezone('Europe/Madrid');
