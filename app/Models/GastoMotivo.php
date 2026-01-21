<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GastoMotivo extends Model
{
    use HasFactory;

    protected $table = 'gastos_motivos';

    protected $fillable = ['nombre'];
}
