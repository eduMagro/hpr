<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Localizacion extends Model
{
    protected $table = 'localizaciones';

    protected $fillable = [
        'x1',
        'y1',
        'x2',
        'y2',
        'tipo',
    ];

    public $timestamps = true; // si usas created_at y updated_at
}
