<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

app(Schedule::class)->command('turnos:generar-anuales')->yearlyOn(1, 1, '00:00');
app(Schedule::class)->command('vacaciones:reset')->yearlyOn(1, 1, '00:00');
