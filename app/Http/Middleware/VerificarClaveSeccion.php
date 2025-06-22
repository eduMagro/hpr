<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class VerificarClaveSeccion
{
    public function handle($request, Closure $next, $seccion)
    {
        if (!Session::get("clave_validada_$seccion")) {
            abort(403, 'Sección protegida por clave.');
        }

        return $next($request);
    }
}
