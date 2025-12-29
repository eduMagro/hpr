<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SnapshotProduccion extends Model
{
    protected $table = 'snapshots_produccion';

    protected $fillable = [
        'tipo_operacion',
        'user_id',
        'orden_planillas_data',
        'elementos_data',
    ];

    protected $casts = [
        'orden_planillas_data' => 'array',
        'elementos_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
