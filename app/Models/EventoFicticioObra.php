<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoFicticioObra extends Model
{
    use HasFactory;

    protected $table = 'eventos_ficticios_obra';

    protected $fillable = [
        'trabajador_ficticio_id',
        'fecha',
        'obra_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function trabajadorFicticio()
    {
        return $this->belongsTo(TrabajadorFicticio::class);
    }
}
