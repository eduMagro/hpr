<?php
namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function created(User $user)
    {
        \Log::info("Usuario creado: {$user->id}");
        // Los turnos ahora se generan manualmente mediante el botón "Turnos" en users.index
    }
}
