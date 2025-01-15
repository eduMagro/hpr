<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etiqueta extends Model
{
    use HasFactory;

    protected $table = 'etiquetas';

    protected $fillable = [
        'planilla_id',
        'elemento_id',
        'numero_etiqueta',
        'nombre',
    ];

    public function planilla()
    {
        return $this->belongsTo(Planilla::class);
    }

    public function elemento()
    {
        return $this->belongsTo(Elemento::class);
    }

}
