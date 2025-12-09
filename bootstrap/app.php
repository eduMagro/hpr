<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\VerificarAccesoSeccion;
use App\Http\Middleware\VerificarPermisoAsistente;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Http\Request;
//use App\Console\Commands\SincronizarFestivosCommand;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'acceso.seccion' => VerificarAccesoSeccion::class,
            'puede.asistente' => VerificarPermisoAsistente::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Redirigir al login cuando expira la sesión (error 419 - Page Expired)
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            return redirect()->route('login')->with('message', 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.');
        });
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
