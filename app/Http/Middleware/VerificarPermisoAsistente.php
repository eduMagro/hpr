<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermisoAsistente
{
    /**
     * Verifica que el usuario tenga permiso para usar el asistente virtual
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Verificar que el usuario esté autenticado
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'No autenticado'
            ], 401);
        }

        // Por ahora, solo administradores pueden usar el asistente
        if ($user->rol !== 'admin') {
            return response()->json([
                'success' => false,
                'error' => 'El asistente virtual solo está disponible para administradores.'
            ], 403);
        }

        return $next($request);
    }
}
