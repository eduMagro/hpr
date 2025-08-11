<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\VerificarAccesoSeccion;
use Illuminate\Console\Scheduling\Schedule;
//use App\Console\Commands\SincronizarFestivosCommand;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'acceso.seccion' => VerificarAccesoSeccion::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withCommands([
        // App\Console\Commands\SincronizarFestivosCommand::class,
    ])

    // Programación de tareas (Schedule)
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('festivos:sincronizar')
            ->yearlyOn(1, 1, '01:10')
            ->timezone('Europe/Madrid');
    })
    ->create();
