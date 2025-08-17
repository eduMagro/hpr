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

        // ✅ Acceso total para admins
        if (in_array($user->email, [
            'eduardo.magro@pacoreyes.com',
            'sebastian.duran@pacoreyes.com',
            'juanjose.dorado@pacoreyes.com'
        ])) {
            return $next($request);
        }

        $rutaActual = $request->route()?->getName() ?? '';
        // ✅ Rutas públicas siempre accesibles
        $rutasLibres = [
            'politica.privacidad',
            'politica.cookies',
            'politicas.aceptar',
            'ayuda.index',
            'usuarios.show',
            'usuarios.index',
        ];

        if (in_array($rutaActual, $rutasLibres)) {
            return $next($request);
        }

        $seccionBase = Str::before($rutaActual, '.');
        $esOperario = $user->rol === 'operario';
        $esOficina  = $user->rol === 'oficina';

        $departamentoAdmin = Departamento::where('nombre', 'Administrador')->first();
        $sinUsuariosAdmin = !$departamentoAdmin || !$departamentoAdmin->usuarios()->exists();
        $sinSeccionesAsignadas = Seccion::whereDoesntHave('departamentos')->count() === Seccion::count();

        // Si aún no hay configuración de permisos, permitir todo
        if ($sinUsuariosAdmin || $sinSeccionesAsignadas) {
            return $next($request);
        }

        // ✅ Permitir solo rutas concretas a operarios
        $permitidosOperarioPrefix = [
            'maquinas.',
            'productos.',
            'users.',
            'alertas.',
            'entradas.',
            'pedidos.',
            'movimientos.',
            'politicas.'
        ];

        $permitidosOperarioRutas = [
            'vacaciones.solicitar',
            'salidas.actualizarEstado',
            'usuarios.editarSubirImagen',

        ];

        if (
            $esOperario &&
            !Str::startsWith($rutaActual, $permitidosOperarioPrefix) &&
            !in_array($rutaActual, $permitidosOperarioRutas)
        ) {
            Log::info('🚫 Ruta denegada para operario: ' . $rutaActual);
            abort(403, 'Operario sin acceso.');
        }


        if ($esOficina) {
            $accion = Str::afterLast($rutaActual, '.');
            $accion = strtolower($accion);

            $seccionBase = Str::before($rutaActual, '.');

            $seccion = Seccion::whereRaw('LOWER(ruta) LIKE ?', [strtolower($seccionBase) . '.%'])->first();
            if (!$seccion) abort(403, 'Sección no registrada.');

            $permisos = PermisoAcceso::where('user_id', $user->id)
                ->where('seccion_id', $seccion->id)
                ->get();

            if ($permisos->isEmpty()) {
                Log::debug('Sin permisos asignados para la sección', [
                    'user' => $user->email,
                    'seccion' => $seccion->ruta,
                    'ruta' => $rutaActual,
                ]);
                abort(403, 'No tienes permisos asignados para esta sección.');
            }

            /**
             * Clasificación automática de acciones según el nombre de la ruta:
             * - puede_ver:     'index', 'show' o empieza por 'ver'
             * - puede_crear:   'create', 'store' o empieza por 'crear'
             * - puede_editar:  'edit', 'update', 'destroy' o empieza por 'editar'
             */

            $autorizado = false;

            foreach ($permisos as $permiso) {
                if (
                    in_array($accion, ['index', 'show']) ||
                    Str::startsWith($accion, 'ver')
                ) {
                    if ($permiso->puede_ver) {
                        $autorizado = true;
                        break;
                    }
                }

                if (
                    in_array($accion, ['create', 'store']) ||
                    Str::startsWith($accion, 'crear')
                ) {
                    if ($permiso->puede_crear) {
                        $autorizado = true;
                        break;
                    }
                }

                if (
                    in_array($accion, ['edit', 'update', 'destroy']) ||
                    Str::startsWith($accion, 'editar')
                ) {
                    if ($permiso->puede_editar) {
                        $autorizado = true;
                        break;
                    }
                }
            }

            if (!$autorizado) {
                Log::warning('❌ Acción no autorizada', [
                    'user' => $user->email,
                    'ruta' => $rutaActual,
                    'accion' => $accion,
                    'seccion' => $seccionBase
                ]);
                abort(403, 'No tienes permisos suficientes para esta acción.');
            }
        }

        return $next($request);
    }
}
