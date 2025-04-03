<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeguridadSocial extends Model
{
    use HasFactory;

    protected $table = 'ss_config';

    protected $fillable = [
        'concepto',
        'porcentaje',
    ];

    public $timestamps = false;
}
