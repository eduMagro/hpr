<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fabricante extends Model
{
    use SoftDeletes;
    protected $table = 'fabricantes';

    protected $fillable = [
        'nombre',
        'telefono',
        'email',
        'direccion',
        'es_siderurgica_sevillana',
    ];

    protected $casts = [
        'es_siderurgica_sevillana' => 'boolean',
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
