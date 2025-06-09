<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Seccion;
use App\Models\Departamento;
use App\Models\PermisoAcceso;
use Illuminate\Support\Facades\Log;

class VerificarAccesoSeccion
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();
        if (!$user) abort(403, 'No autenticado.');
        // ✅ Acceso total para Eduardo
        if ($user->email === 'eduardo.magro@pacoreyes.com') {
            return $next($request);
        }
        $rutaActual = $request->route()->getName(); // ej: departamentos.edit

        $seccionBase = Str::before($rutaActual, '.'); // ej: departamentos

        $esOperario = $user->rol === 'operario';
        $esOficina = $user->rol === 'oficina';

        $departamentoAdmin = Departamento::where('nombre', 'Administrador')->first();
        $sinUsuariosAdmin = !$departamentoAdmin || !$departamentoAdmin->usuarios()->exists();

        $sinSeccionesAsignadas = Seccion::whereDoesntHave('departamentos')->count() === Seccion::count();

        if ($sinUsuariosAdmin || $sinSeccionesAsignadas) {
            return $next($request);
        }
        // Log::info('✅ Ruta actual: ' . $rutaActual);
        // Log::info('✅ Usuario: ' . $user->name . ' | Rol: ' . $user->rol);

        // Permitir solo ciertas rutas a operarios
        $permitidosOperario = ['maquinas.', 'productos.', 'users.', 'alertas.', 'entradas.', 'pedidos.'];

        if ($esOperario && !Str::startsWith($rutaActual, $permitidosOperario)) {
            Log::info('🚫 Ruta denegada para operario: ' . $rutaActual);
            abort(403, 'Operario sin acceso.');
        }


        if ($esOficina) {
            $rutaCompleta = $request->route()->getName(); // ej: usuarios.edit
            $accion = Str::afterLast($rutaCompleta, '.'); // ej: 'edit', 'index', 'create', 'destroy'

            $seccionBase = Str::before($rutaCompleta, '.');

            $seccion = Seccion::whereRaw('LOWER(ruta) LIKE ?', [strtolower($seccionBase) . '.%'])->first();
            if (!$seccion) abort(403, 'Sección no registrada.');

            $permisos = PermisoAcceso::where('user_id', $user->id)
                ->where('seccion_id', $seccion->id)
                ->get();

            if ($permisos->isEmpty()) {
                abort(403, 'No tienes permisos asignados para esta sección.');
            }

            $autorizado = false;

            foreach ($permisos as $permiso) {
                if (in_array($accion, ['index', 'show']) && $permiso->puede_ver) $autorizado = true;
                if (in_array($accion, ['edit', 'update']) && $permiso->puede_editar) $autorizado = true;
                if (in_array($accion, ['create', 'store']) && $permiso->puede_crear) $autorizado = true;
            }

            if (!$autorizado) {
                abort(403, 'No tienes permisos suficientes para esta acción.');
            }
        }

        return $next($request);
    }
}
