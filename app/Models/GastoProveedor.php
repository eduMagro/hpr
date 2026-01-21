<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GastoProveedor extends Model
{
    use HasFactory;

    protected $table = 'gastos_proveedores';

    protected $fillable = ['nombre'];
}
