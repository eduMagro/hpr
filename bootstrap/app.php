<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\VerificarAccesoSeccion;
use App\Http\Middleware\VerificarPermisoAsistente;
use App\Http\Middleware\FerrawinApiAuth;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
//use App\Console\Commands\SincronizarFestivosCommand;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Confiar en todos los proxies para obtener IP real del cliente
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'acceso.seccion' => VerificarAccesoSeccion::class,
            'puede.asistente' => VerificarPermisoAsistente::class,
            'ferrawin.api' => FerrawinApiAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Redirigir al login cuando expira la sesión (error 419 - Page Expired)
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            return redirect()->route('login')->with('message', 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.');
        });

        // Manejar errores 403 (Acceso denegado)
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'error' => 'No tienes permiso para acceder.',
                    'message' => 'No tienes permiso para acceder.'
                ], 403);
            }
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para acceder a esa sección.');
        });

        // Manejar otros errores HTTP con mensajes amigables
        $exceptions->render(function (HttpException $e, Request $request) {
            $statusCode = $e->getStatusCode();
            $mensajes = [
                403 => 'No tienes permiso para acceder.',
                404 => 'La página que buscas no existe.',
                500 => 'Ha ocurrido un error en el servidor.',
            ];

            $mensaje = $mensajes[$statusCode] ?? 'Ha ocurrido un error.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => $mensaje, 'message' => $mensaje], $statusCode);
            }

            // Solo redirigir para errores 403, los demás usar vista de error estándar
            if ($statusCode === 403) {
                return redirect()->route('dashboard')->with('error', $mensaje);
            }

            return null; // Dejar que Laravel maneje los demás errores
        });

        // Manejar errores 500 (Server Error) - captura todas las excepciones no manejadas
        $exceptions->render(function (\Throwable $e, Request $request) {
            // Ignorar excepciones HTTP ya manejadas arriba
            if ($e instanceof HttpException || $e instanceof TokenMismatchException || $e instanceof AccessDeniedHttpException) {
                return null;
            }

            // Ignorar errores 404
            if ($e instanceof NotFoundHttpException) {
                return null;
            }

            // Para peticiones AJAX/JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'error' => 'Error 500: Error del servidor',
                    'message' => 'Ha ocurrido un error interno en el servidor.',
                    'server_error' => true
                ], 500);
            }

            // Para navegación normal: redirigir con session flash para SweetAlert
            return redirect()->route('dashboard')->with('server_error', [
                'title' => 'Error 500: Error del servidor',
                'message' => 'Ha ocurrido un error interno en el servidor. Puedes reportar este error para que el equipo técnico lo revise.',
                'url_origen' => $request->fullUrl(),
                'exception' => config('app.debug') ? $e->getMessage() : null
            ]);
        });
    })
    ->withCommands([
        // App\Console\Commands\SincronizarFestivosCommand::class,
    ])

    // Programación de tareas definida en routes/console.php
    ->create();
