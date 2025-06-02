<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Seccion;
use App\Models\Departamento;
use Illuminate\Support\Facades\Log;

class VerificarAccesoSeccion
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();
        if (!$user) abort(403, 'No autenticado.');

        $rutaActual = $request->route()->getName(); // ej: departamentos.edit

        // ⚠️ Permitir crear o guardar secciones libremente
        if (in_array($rutaActual, ['secciones.create', 'secciones.store'])) {
            return $next($request);
        }

        $seccionBase = Str::before($rutaActual, '.'); // ej: departamentos

        $esOperario = $user->rol === 'operario';
        $esOficina = $user->rol === 'oficina';

        $departamentoAdmin = Departamento::where('nombre', 'Administrador')->first();
        $sinUsuariosAdmin = !$departamentoAdmin || !$departamentoAdmin->usuarios()->exists();

        $sinSeccionesAsignadas = \App\Models\Seccion::whereDoesntHave('departamentos')->count() === \App\Models\Seccion::count();

        if ($sinUsuariosAdmin || $sinSeccionesAsignadas) {
            return $next($request);
        }

        // Permitir solo ciertas rutas a operarios
        $permitidosOperario = ['maquinas.', 'productos.', 'users.', 'alertas.'];
        if ($esOperario && !Str::startsWith($rutaActual, $permitidosOperario)) {
            abort(403, 'Operario sin acceso.');
        }

        // Validación para oficina basada en el nombre de la sección
        if ($esOficina) {
            $departamentosUsuario = $user->departamentos->pluck('id')->toArray();

            $seccion = Seccion::whereRaw('LOWER(ruta) LIKE ?', [strtolower($seccionBase) . '.%'])
                ->with('departamentos')
                ->first();


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
