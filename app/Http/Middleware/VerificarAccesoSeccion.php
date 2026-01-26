<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\PermissionService;

class VerificarAccesoSeccion
{
    public function __construct(
        protected PermissionService $permissions
    ) {
    }

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
        $this->logAcceso('warning', $mensaje, array_merge([
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ], $context));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => $mensaje, 'message' => $mensaje], 403);
        }

        return redirect()->route('dashboard')->with('error', $mensaje);
    }

    public function handle(Request $request, Closure $next): mixed
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->denegarAcceso($request, 'No autenticado.');
            }

            $routeName = $request->route()?->getName() ?? '';

            // Verificar acceso usando el servicio centralizado
            if ($this->permissions->canAccessRoute($user, $routeName)) {
                // Para rol oficina, verificar también la acción específica
                if (strtolower($user->rol ?? '') === 'oficina') {
                    if ($this->permissions->canPerformAction($user, $routeName)) {
                        return $next($request);
                    }

                    return $this->denegarAcceso($request, "No tienes permisos suficientes para realizar esta acción. (Ruta: {$routeName})", [
                        'usuario' => $user->email,
                        'ruta' => $routeName,
                        'tipo' => 'accion_no_autorizada',
                    ]);
                }

                return $next($request);
            }

            return $this->denegarAcceso($request, "No tienes permiso para acceder a esta sección. (Ruta: {$routeName})", [
                'usuario' => $user->email,
                'ruta' => $routeName,
                'rol' => $user->rol,
                'tipo' => 'sin_acceso',
            ]);
        } catch (\Throwable $e) {
            // Si hay un error en el servicio de permisos, loguear y permitir acceso
            // para evitar bloquear al usuario por errores técnicos
            Log::error('Error en verificación de permisos', [
                'error' => $e->getMessage(),
                'ruta' => $request->route()?->getName(),
                'url' => $request->fullUrl(),
            ]);

            // Para rutas AJAX/API, devolver error controlado en lugar de 500
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'error' => 'Error temporal de permisos',
                    'cantidad' => 0 // Para compatibilidad con alertas/sin-leer
                ], 200);
            }

            // Para peticiones normales, permitir continuar
            return $next($request);
        }
    }
}
