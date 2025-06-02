<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Departamento extends Model
{
    // Tabla asociada (opcional si sigue la convención)
    protected $table = 'departamentos';

    // Asignación masiva
    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    // Relación muchos a muchos con usuarios
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('rol_departamental')
            ->withTimestamps();
    }
    public function secciones()
    {
        return $this->belongsToMany(Seccion::class);
    }
}
