<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartamentoRuta extends Model
{
    protected $table = 'departamento_ruta';

    protected $fillable = [
        'departamento_id',
        'ruta',
        'descripcion',
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }
}
