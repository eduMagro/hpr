<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Seccion;

class VerificarAccesoSeccion
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();
        if (!$user) abort(403, 'No autenticado.');

        $rutaActual = $request->route()->getName();
        $esOperario = $user->rol === 'operario';
        $esOficina = $user->rol === 'oficina';

        $permitidosOperario = [
            'maquinas.',
            'productos.',
            'users.',
            'alertas.',
        ];

        // Verificación para operarios: solo por prefijos permitidos
        if ($esOperario && !Str::startsWith($rutaActual, $permitidosOperario)) {
            abort(403, 'Operario sin acceso.');
        }

        // Verificación para oficina: consulta en la tabla secciones
        if ($esOficina) {
            $departamentosUsuario = $user->departamentos->pluck('id')->toArray();

            $seccion = Seccion::where('ruta', $rutaActual)->with('departamentos')->first();
            if (!$seccion) {
                abort(403, 'Sección no registrada.');
            }

            $idsDepSeccion = $seccion->departamentos->pluck('id')->toArray();

            if (!array_intersect($departamentosUsuario, $idsDepSeccion)) {
                abort(403, 'No tienes permisos para esta sección.');
            }
        }

        return $next($request);
    }
}
