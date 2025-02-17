<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salida extends Model
{
    protected $fillable = ['camion'];

    public function planillas()
    {
        return $this->belongsToMany(Planilla::class, 'planilla_salida');
    }
}
