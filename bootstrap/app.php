<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\VerificarAccesoSeccion;
use Illuminate\Contracts\Debug\ExceptionHandler;
use App\Exceptions\Handler;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'acceso.seccion' => \App\Http\Middleware\VerificarAccesoSeccion::class,
        ]);
    })
    ->withExceptions(function () {
        // Aquí no pongas nada, lo dejamos limpio
    })
    ->create()
    ->singleton(ExceptionHandler::class, Handler::class); // ✅ ESTE es el bueno
