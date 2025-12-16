<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de autenticación para la API de FerraWin.
 *
 * Valida el token de API enviado en el header Authorization.
 */
class FerrawinApiAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autorización requerido',
            ], 401);
        }

        $validToken = config('ferrawin.api.token');

        if (!$validToken || !hash_equals($validToken, $token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido',
            ], 403);
        }

        return $next($request);
    }
}
