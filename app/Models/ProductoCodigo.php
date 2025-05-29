<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoCodigo extends Model
{
    public $timestamps = false;

    protected $table = 'productos_codigos';

    protected $fillable = ['tipo', 'anio', 'ultimo_numero'];
}
