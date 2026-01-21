<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $fillable = ['ruta'];

    public function gastos()
    {
        return $this->hasMany(Gasto::class);
    }
}
