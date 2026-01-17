<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Canal privado para control de sincronización FerraWin.
 *
 * Autorización:
 * - Usuarios autenticados con permiso 'sync:control'
 * - O usuarios con rol 'admin'
 *
 * Este canal es usado para:
 * - Enviar comandos start/pause desde producción al cliente Windows
 * - Recibir actualizaciones de estado desde el cliente Windows
 */
Broadcast::channel('sync-control', function ($user) {
    // Verificar si el usuario tiene el permiso específico
    if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('sync:control')) {
        return true;
    }

    // Fallback: verificar si es admin
    if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
        return true;
    }

    // Verificar por atributo is_admin si existe
    if (property_exists($user, 'is_admin') && $user->is_admin) {
        return true;
    }

    return false;
});
