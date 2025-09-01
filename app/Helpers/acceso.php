<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Seccion;

/* =========================
   Superusuarios por email
   ========================= */

if (!function_exists('emailsSuperUsuarios')) {
    function emailsSuperUsuarios(): array
    {
        return array_map(
            fn($e) => strtolower(trim($e)),
            config('acceso.super_emails', [])
        );
    }
}

if (!function_exists('esSuperUsuario')) {
    function esSuperUsuario(?string $email): bool
    {
        if (!$email) return false;
        return in_array(strtolower(trim($email)), emailsSuperUsuarios(), true);
    }
}

/* =========================
   Whitelists por rol
   ========================= */
if (!function_exists('rutasPermitidasOperario')) {
    function rutasPermitidasOperario(): array
    {
        return config('acceso.rutas_operario', [
            'produccion.trabajadores',
            'users.',
            'alertas.',
            'productos.',
            'pedidos.',
            'ayuda.',
            'maquinas.',
            'entradas.',
        ]);
    }
}

if (!function_exists('rutasPermitidasTransportista')) {
    function rutasPermitidasTransportista(): array
    {
        return config('acceso.rutas_transportista', [
            'users.',
            'alertas.',
            'ayuda.',
            'planificacion.',
            'salidas.',
            'usuarios.',
            'nominas.',
        ]);
    }
}

/* =========================
   Regla principal de acceso
   ========================= */
if (!function_exists('usuarioTieneAcceso')) {
    function usuarioTieneAcceso(string $ruta): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        // 1) Superusuarios: acceso total
        if (esSuperUsuario($user->email)) {
            return true;
        }

        // 2) Reglas por rol permitido
        switch ($user->rol) {
            case 'operario':
                return collect(rutasPermitidasOperario())
                    ->contains(fn($permitida) => Str::startsWith($ruta, $permitida) || $ruta === $permitida);

            case 'transportista':
                return collect(rutasPermitidasTransportista())
                    ->contains(fn($permitida) => Str::startsWith($ruta, $permitida) || $ruta === $permitida);

            case 'oficina':
                // Oficina: según secciones asignadas a departamentos
                $departamentosUsuario = $user->departamentos->pluck('id')->all();

                // Base = antes del primer punto (p.ej. 'usuarios' de 'usuarios.update')
                $base   = Str::before($ruta, '.');
                $seccion = Seccion::whereRaw('LOWER(ruta) LIKE ?', [strtolower($base) . '.%'])
                    ->with('departamentos')
                    ->first();

                // Si no se encontró por prefijo, intenta exacta
                if (!$seccion) {
                    $seccion = Seccion::where('ruta', $ruta)->with('departamentos')->first();
                    if (!$seccion) return false;
                }

                $departamentosSeccion = $seccion->departamentos->pluck('id')->all();
                return count(array_intersect($departamentosUsuario, $departamentosSeccion)) > 0;

            default:
                // 3) Cualquier otro rol: DENEGAR por defecto
                return false;
        }
    }
}
