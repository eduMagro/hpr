<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistribuidorDireccion extends Model
{
    protected $table = 'distribuidor_direcciones';

    protected $fillable = [
        'distribuidor_id',
        'direccion_match',
    ];

    /**
     * Relación con Distribuidor
     */
    public function distribuidor(): BelongsTo
    {
        return $this->belongsTo(Distribuidor::class);
    }
}
