<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IrpfTramo extends Model
{
    use HasFactory;

    protected $table = 'irpf_tramos';

    protected $fillable = [
        'tramo_inicial',
        'tramo_final',
        'porcentaje'
    ];

    public $timestamps = false;
}
