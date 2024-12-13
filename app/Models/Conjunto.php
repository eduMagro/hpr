<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conjunto extends Model
{
    use HasFactory;

    protected $table = 'conjuntos';

    protected $fillable = [
        'planilla_id',
        'codigo',
        'nombre',
        'descripcion',
    ];

    public $timestamps = true;

    /**
     * Obtiene la planilla a la que pertenece el conjunto.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id', 'id');
    }

    /**
     * Obtiene los elementos asociados al conjunto.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'conjunto_id', 'id');
    }
}
