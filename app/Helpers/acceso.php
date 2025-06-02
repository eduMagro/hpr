<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('usuarioTieneAcceso')) {
    function usuarioTieneAcceso(string $ruta): bool
    {
        $user = Auth::user();

        if (!$user) return false;

        // Operario
        if ($user->rol === 'operario') {
            $permitidosOperario = [
                'produccion.trabajadores',
                'users.index',
                'alertas.index',
                'productos.index',
                'users.index',
                'maquinas.index',
            ];
            return in_array($ruta, $permitidosOperario);
        }

        // Oficina
        if ($user->rol === 'oficina') {
            $departamentosUsuario = $user->departamentos->pluck('id')->toArray();
            $seccion = \App\Models\Seccion::where('ruta', $ruta)->with('departamentos')->first();

            if (!$seccion) return false;

            $permitidos = $seccion->departamentos->pluck('id')->toArray();
            return count(array_intersect($departamentosUsuario, $permitidos)) > 0;
        }

        return true; // Por defecto permitir (por ejemplo, administrador)
    }
}
