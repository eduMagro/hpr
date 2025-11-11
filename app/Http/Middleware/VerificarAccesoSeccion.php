<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\Seccion;
use App\Models\PermisoAcceso;
use App\Models\Empresa;

class VerificarAccesoSeccion
{
    public function handle(Request $request, Closure $next): mixed
    {
        $usuarioAutenticado = Auth::user();
        if (!$usuarioAutenticado) {
            abort(403, 'No autenticado.');
        }

        $correoUsuario = strtolower(trim($usuarioAutenticado->email));
        $nombreRutaActual = $request->route()?->getName() ?? '';
        $empresaUsuarioId = $usuarioAutenticado->empresa_id;
        $rolUsuario = strtolower((string) $usuarioAutenticado->rol);

        // === 1) Acceso total por correo (desde config/acceso.php) ===
        $correosAccesoTotal = config('acceso.correos_acceso_total', []);
        if (in_array($correoUsuario, $correosAccesoTotal, true)) {
            return $next($request);
        }

        // === 2) Cachear IDs de empresas clave ===
        $empresaReyesTejeroId = Cache::remember('empresa_id_reyes_tejero', 86400, function () {
            return Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%reyes tejero%'])->value('id');
        });
        $empresaHPRId = Cache::remember('empresa_id_hpr', 86400, function () {
            return Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%hierros paco reyes%'])->value('id');
        });
        $empresaServiciosId = Cache::remember('empresa_id_hpr_servicios', 86400, function () {
            return Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%hpr servicios%'])->value('id');
        });

        $empresasConAccesoCompleto = [$empresaHPRId, $empresaServiciosId];

        // === 3) Rutas libres (desde config/acceso.php) ===
        $rutasLibres = config('acceso.rutas_libres', []);
        if (
            (in_array($empresaUsuarioId, $empresasConAccesoCompleto, true) && in_array($nombreRutaActual, $rutasLibres, true)) ||
            ($empresaUsuarioId === $empresaReyesTejeroId && in_array($nombreRutaActual, $rutasLibres, true))
        ) {
            return $next($request);
        }

        // === 4) Roles y permisos ===
        if ($rolUsuario === 'operario') {
            $prefijosOperario = config('acceso.prefijos_operario', []);
            $permitido = collect($prefijosOperario)->contains(
                fn($prefijo) => $nombreRutaActual === $prefijo || Str::startsWith($nombreRutaActual, $prefijo)
            );

            if (!$permitido) {
                Log::info('🚫 Ruta denegada para operario', [
                    'usuario' => $usuarioAutenticado->email,
                    'ruta' => $nombreRutaActual,
                ]);
                abort(403, 'Operario sin acceso.');
            }
            return $next($request);
        }

        if ($rolUsuario === 'transportista') {
            $prefijosTransportista = config('acceso.prefijos_transportista', []);
            $permitido = collect($prefijosTransportista)->contains(
                fn($prefijo) => $nombreRutaActual === $prefijo || Str::startsWith($nombreRutaActual, $prefijo)
            );

            if (!$permitido) {
                Log::info('🚫 Ruta denegada para transportista', [
                    'usuario' => $usuarioAutenticado->email,
                    'ruta' => $nombreRutaActual,
                ]);
                abort(403, 'Transportista sin acceso.');
            }
            return $next($request);
        }

        if (
            $rolUsuario === 'oficina' && (
                in_array($empresaUsuarioId, $empresasConAccesoCompleto, true)
                || $empresaUsuarioId === $empresaReyesTejeroId
            )
        ) {
            $accionRuta = strtolower(Str::afterLast($nombreRutaActual, '.'));
            $seccionBase = Str::before($nombreRutaActual, '.');

            $seccion = Seccion::whereRaw('LOWER(ruta) LIKE ?', [strtolower($seccionBase) . '.%'])->first();
            if (!$seccion) {
                Log::warning('❌ Ruta sin sección registrada', ['ruta' => $nombreRutaActual]);
                abort(403, 'Sección no registrada.');
            }

            $permisos = PermisoAcceso::where('user_id', $usuarioAutenticado->id)
                ->where('seccion_id', $seccion->id)
                ->get();

            if ($permisos->isEmpty()) {
                Log::debug('❌ Sin permisos para sección', [
                    'usuario' => $usuarioAutenticado->email,
                    'seccion' => $seccion->ruta,
                    'ruta' => $nombreRutaActual,
                ]);
                abort(403, 'No tienes permisos asignados para esta sección.');
            }

            $autorizado = false;
            foreach ($permisos as $permiso) {
                if (
                    // Permiso de VER
                    (in_array($accionRuta, ['index', 'show']) || Str::startsWith($accionRuta, 'ver') || Str::startsWith($accionRuta, 'show')) && $permiso->puede_ver

                    // Permiso de CREAR
                    || (in_array($accionRuta, ['create', 'store']) || Str::startsWith($accionRuta, 'crear') || Str::startsWith($accionRuta, 'store')) && $permiso->puede_crear

                    // Permiso de EDITAR (aquí está el fix)
                    || (in_array($accionRuta, ['edit', 'update', 'destroy'])
                        || Str::startsWith($accionRuta, 'editar')
                        || Str::startsWith($accionRuta, 'actualizar')
                        || Str::startsWith($accionRuta, 'update')  // ← AGREGAR ESTA LÍNEA
                        || Str::startsWith($accionRuta, 'destroy')
                        || Str::startsWith($accionRuta, 'delete')
                        || Str::startsWith($accionRuta, 'eliminar')
                    ) && $permiso->puede_editar
                ) {
                    $autorizado = true;
                    break;
                }
            }
            if (!$autorizado) {
                Log::warning('❌ Acción no autorizada', [
                    'usuario' => $usuarioAutenticado->email,
                    'ruta' => $nombreRutaActual,
                    'accion' => $accionRuta,
                    'seccion' => $seccionBase,
                ]);
                abort(403, 'No tienes permisos suficientes para esta acción.');
            }

            return $next($request);
        }

        // === 5) Denegación por defecto ===
        Log::warning('🚫 Ruta denegada por configuración (sin coincidencias)', [
            'usuario' => $usuarioAutenticado->email,
            'empresa_id' => $empresaUsuarioId,
            'ruta' => $nombreRutaActual,
            'rol' => $rolUsuario,
        ]);
        abort(403, 'Acceso denegado por configuración.');
    }
}
