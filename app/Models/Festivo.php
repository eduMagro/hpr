<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Festivo extends Model
{
    use HasFactory;

    protected $table = 'festivos';

    protected $fillable = [
        'titulo',
        'fecha',
        'editable',
        'anio',
    ];

    protected $casts = [
        'fecha' => 'date',
        'editable' => 'boolean',
    ];

    // Scope para filtrar por año
    public function scopeDelAnio($query, $anio)
    {
        return $query->where('anio', $anio);
    }
}
