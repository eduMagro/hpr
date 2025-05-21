<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenPlanilla extends Model
{
    protected $table = 'orden_planillas';

    protected $fillable = [
        'planilla_id',
        'maquina_id',
        'posicion',
    ];
    public function planilla()
    {
        return $this->belongsTo(Planilla::class);
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class);
    }
}
