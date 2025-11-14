<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Localizacion extends Model
{
    use SoftDeletes;
    protected $table = 'localizaciones';

    protected $fillable = [
        'x1',
        'y1',
        'x2',
        'y2',
        'tipo',
        'maquina_id',
        'nave_id',
        'nombre',
    ];


    public $timestamps = true; // si usas created_at y updated_at

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }
    public function nave()
    {
        return $this->belongsTo(Obra::class, 'nave_id');
    }
}
