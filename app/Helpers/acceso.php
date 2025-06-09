<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// ✅ Rutas permitidas para operarios (pueden ser exactas o prefijos)
if (!function_exists('rutasPermitidasOperario')) {
    function rutasPermitidasOperario(): array
    {
        return [
            'produccion.trabajadores',
            'users.',
            'alertas.',
            'productos.',
            'pedidos.',
            'ayuda.',
            'maquinas.',
            'entradas.',
        ];
    }
}

// ✅ Función principal de validación de acceso
if (!function_exists('usuarioTieneAcceso')) {
    function usuarioTieneAcceso(string $ruta): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        // 🔒 Operario: solo acceso a rutas permitidas
        if ($user->rol === 'operario') {
            return collect(rutasPermitidasOperario())
                ->contains(fn($permitida) => Str::startsWith($ruta, $permitida));
        }

        // 🧾 Oficina: validar con secciones asignadas a sus departamentos
        if ($user->rol === 'oficina') {
            $departamentosUsuario = $user->departamentos->pluck('id')->toArray();

            $seccion = \App\Models\Seccion::where('ruta', $ruta)
                ->with('departamentos')
                ->first();

            if (!$seccion) return false;

            $departamentosSeccion = $seccion->departamentos->pluck('id')->toArray();

            return count(array_intersect($departamentosUsuario, $departamentosSeccion)) > 0;
        }

        // ✅ Otros roles (admin, etc.) tienen acceso total
        return true;
    }
}
