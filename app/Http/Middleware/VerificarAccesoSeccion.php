<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Seccion;
use App\Models\PermisoAcceso;
use App\Models\Empresa;

class VerificarAccesoSeccion
{
    /**
     * Log en el canal de accesos
     */
    private function logAcceso(string $level, string $mensaje, array $context = []): void
    {
        Log::channel('accesos')->{$level}($mensaje, $context);
    }

    /**
     * Deniega el acceso con redirect o JSON según el tipo de petición
     */
    private function denegarAcceso(Request $request, string $mensaje, array $context = [])
    {
        $this->logAcceso('warning', '🚫 ' . $mensaje, array_merge([
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ], $context));

        // Si es petición AJAX o espera JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => $mensaje, 'message' => $mensaje], 403);
        }

        // Redirect al dashboard con mensaje de error
        return redirect()->route('dashboard')->with('error', $mensaje);
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $usuarioAutenticado = Auth::user();
        if (!$usuarioAutenticado) {
            return $this->denegarAcceso($request, 'No autenticado.');
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

        // === 2) Acceso total para usuarios del departamento Administrador ===
        $esAdministrador = $usuarioAutenticado->departamentos()
            ->whereRaw('LOWER(nombre) = ?', ['administrador'])
            ->exists();

        if ($esAdministrador) {
            return $next($request);
        }

        // === 3) Cachear IDs de empresas clave ===
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

        // === 4) Rutas libres (desde config/acceso.php) ===
        $rutasLibres = config('acceso.rutas_libres', []);
        if (
            (in_array($empresaUsuarioId, $empresasConAccesoCompleto, true) && in_array($nombreRutaActual, $rutasLibres, true)) ||
            ($empresaUsuarioId === $empresaReyesTejeroId && in_array($nombreRutaActual, $rutasLibres, true))
        ) {
            return $next($request);
        }

        // === 5) Roles y permisos ===
        if ($rolUsuario === 'operario') {
            try {
                // Los operarios usan las rutas del departamento "Operario" (sin necesidad de asignación)
                $departamentoOperarioId = Cache::remember('departamento_id_operario', 86400, function () {
                    return \App\Models\Departamento::whereRaw('LOWER(nombre) = ?', ['operario'])->value('id');
                });

                if ($departamentoOperarioId) {
                    // Verificar si la tabla existe antes de consultar
                    $tablaExiste = Cache::remember('tabla_departamento_ruta_existe', 3600, function () {
                        return \Schema::hasTable('departamento_ruta');
                    });

                    if ($tablaExiste) {
                        // Obtener las rutas permitidas del departamento "Operario"
                        $rutasPermitidas = Cache::remember("rutas_departamento_{$departamentoOperarioId}", 300, function () use ($departamentoOperarioId) {
                            return \DB::table('departamento_ruta')
                                ->where('departamento_id', $departamentoOperarioId)
                                ->pluck('ruta')
                                ->toArray();
                        });

                        // Verificar si la ruta actual está permitida
                        $permitido = collect($rutasPermitidas)->contains(function ($rutaPermitida) use ($nombreRutaActual) {
                            // Si termina en .* es un prefijo (ej: "usuarios.*" permite "usuarios.index", "usuarios.show", etc.)
                            if (Str::endsWith($rutaPermitida, '.*')) {
                                $prefijo = Str::beforeLast($rutaPermitida, '.*');
                                return $nombreRutaActual === $prefijo || Str::startsWith($nombreRutaActual, $prefijo . '.');
                            }
                            // Si no, es una ruta exacta
                            return $nombreRutaActual === $rutaPermitida;
                        });

                        if ($permitido) {
                            return $next($request);
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logAcceso('error', 'Error verificando permisos de operario', [
                    'error' => $e->getMessage(),
                    'usuario' => $usuarioAutenticado->email,
                    'ruta' => $nombreRutaActual,
                ]);
            }

            return $this->denegarAcceso($request, 'No tienes permiso para acceder.', [
                'usuario' => $usuarioAutenticado->email,
                'ruta' => $nombreRutaActual,
                'rol' => 'operario',
            ]);
        }

        if ($rolUsuario === 'transportista') {
            $prefijosTransportista = config('acceso.prefijos_transportista', []);
            $permitido = collect($prefijosTransportista)->contains(
                fn($prefijo) => $nombreRutaActual === $prefijo || Str::startsWith($nombreRutaActual, $prefijo)
            );

            if (!$permitido) {
                return $this->denegarAcceso($request, 'No tienes permiso para acceder.', [
                    'usuario' => $usuarioAutenticado->email,
                    'ruta' => $nombreRutaActual,
                    'rol' => 'transportista',
                ]);
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
                return $this->denegarAcceso($request, "La sección '{$seccionBase}' no está registrada en el sistema.", [
                    'usuario' => $usuarioAutenticado->email,
                    'ruta' => $nombreRutaActual,
                    'seccion_base' => $seccionBase,
                    'tipo' => 'seccion_no_registrada',
                ]);
            }

            $permisos = PermisoAcceso::where('user_id', $usuarioAutenticado->id)
                ->where('seccion_id', $seccion->id)
                ->get();

            if ($permisos->isEmpty()) {
                return $this->denegarAcceso($request, "No tienes permisos asignados para la sección '{$seccion->nombre}'.", [
                    'usuario' => $usuarioAutenticado->email,
                    'seccion' => $seccion->ruta,
                    'ruta' => $nombreRutaActual,
                    'tipo' => 'sin_permisos_seccion',
                ]);
            }

            $autorizado = false;
            foreach ($permisos as $permiso) {
                if (
                    // Permiso de VER
                    (in_array($accionRuta, ['index', 'show']) || Str::startsWith($accionRuta, 'ver') || Str::startsWith($accionRuta, 'show')) && $permiso->puede_ver

                    // Permiso de CREAR
                    || (in_array($accionRuta, ['create', 'store']) || Str::startsWith($accionRuta, 'crear') || Str::startsWith($accionRuta, 'store')) && $permiso->puede_crear

                    // Permiso de EDITAR
                    || (in_array($accionRuta, ['edit', 'update', 'destroy'])
                        || Str::startsWith($accionRuta, 'editar')
                        || Str::startsWith($accionRuta, 'actualizar')
                        || Str::startsWith($accionRuta, 'update')
                        || Str::startsWith($accionRuta, 'destroy')
                        || Str::startsWith($accionRuta, 'delete')
                        || Str::startsWith($accionRuta, 'eliminar')
                        || Str::startsWith($accionRuta, 'activar')
                    ) && $permiso->puede_editar
                ) {
                    $autorizado = true;
                    break;
                }
            }
            if (!$autorizado) {
                return $this->denegarAcceso($request, 'No tienes permisos suficientes para realizar esta acción.', [
                    'usuario' => $usuarioAutenticado->email,
                    'ruta' => $nombreRutaActual,
                    'accion' => $accionRuta,
                    'seccion' => $seccionBase,
                    'tipo' => 'accion_no_autorizada',
                ]);
            }

            return $next($request);
        }

        // === 6) Denegación por defecto ===
        return $this->denegarAcceso($request, 'No tienes permiso para acceder.', [
            'usuario' => $usuarioAutenticado->email,
            'empresa_id' => $empresaUsuarioId,
            'ruta' => $nombreRutaActual,
            'rol' => $rolUsuario,
            'tipo' => 'sin_coincidencias',
        ]);
    }
}
