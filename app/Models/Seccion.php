<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seccion extends Model
{
    protected $table = 'secciones';
    // 🔐 Campos que pueden asignarse en masa
    protected $fillable = [
        'nombre',
        'ruta',
        'icono',
    ];

    // ✅ Asegura que los timestamps estén activos
    public $timestamps = true;

    // 🔁 Relación con Departamentos (muchos a muchos)
    public function departamentos()
    {
        return $this->belongsToMany(Departamento::class, 'departamento_seccion');
    }
}
