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
        if ($user->email === 'eduardo.magro@pacoreyes.com' || $user->email === 'sebastian.duran@pacoreyes.com') {
            return $next($request);
        }
        $rutaActual = $request->route()->getName(); // ej: departamentos.edit
        // ✅ Permitir acceso libre a la ruta de perfil
        if (in_array($rutaActual, ['perfil.show', 'perfil.index'])) {
            return $next($request);
        }

        $seccionBase = Str::before($rutaActual, '.'); // ej: departamentos

        $esOperario = $user->rol === 'operario';
        $esOficina = $user->rol === 'oficina';

        $departamentoAdmin = Departamento::where('nombre', 'Administrador')->first();
        $sinUsuariosAdmin = !$departamentoAdmin || !$departamentoAdmin->usuarios()->exists();

        $sinSeccionesAsignadas = Seccion::whereDoesntHave('departamentos')->count() === Seccion::count();
        //Si aun no hemos denominado administrados o no hay secciones asignadas a ningun departamento tendremos acceso a todo, porque estara en desarrollo la app.
        if ($sinUsuariosAdmin || $sinSeccionesAsignadas) {
            return $next($request);
        }
        // Log::info('✅ Ruta actual: ' . $rutaActual);
        // Log::info('✅ Usuario: ' . $user->name . ' | Rol: ' . $user->rol);

        // Permitir solo ciertas rutas a operarios
        $permitidosOperario = ['maquinas.', 'productos.', 'users.', 'alertas.', 'entradas.', 'pedidos.', 'movimientos.'];

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
                // Dentro de acceso.seccion, antes de abortar:
                Log::debug('permiso a chequear', [
                    'permiso' => $permisos,
                    'route'   => $request->route()->getName(),
                ]);
                abort(403, 'No tienes permisos asignados para esta sección.');
            }

            $autorizado = false;
            $accionesVer    = ['index', 'show'];
            $accionesCrear  = ['create', 'store'];
            $accionesEditar = ['edit', 'update', 'destroy', 'consumir', 'duplicar', 'liberar']; // ← añade aquí
            foreach ($permisos as $permiso) {
                if (in_array($accion, $accionesVer)   && $permiso->puede_ver)    $autorizado = true;
                if (in_array($accion, $accionesCrear) && $permiso->puede_crear)  $autorizado = true;
                if (in_array($accion, $accionesEditar) && $permiso->puede_editar) $autorizado = true;
            }

            if (!$autorizado) {
                abort(403, 'No tienes permisos suficientes para esta acción.');
            }
        }

        return $next($request);
    }
}
