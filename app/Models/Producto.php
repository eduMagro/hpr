<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'producto_base_id',
        'fabricante_id',
        'distribuidor_id',
        'obra_id',
        'entrada_id',
        'n_colada',
        'colada_id',
        'n_paquete',
        'peso_inicial',
        'peso_stock',
        'ubicacion_id',
        'maquina_id',
        'estado',
        'fecha_consumido',
        'consumido_by',
        'otros',
        'updated_by',
    ];

    public $timestamps = true;
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];



    public function setCodigoAttribute($value)
    {
        $this->attributes['codigo'] = strtoupper($value);
    }

    public function entrada()
    {
        return $this->belongsTo(Entrada::class);
    }
    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    /**
     * Relación con la tabla 'ubicaciones'
     * Un producto pertenece a una ubicación
     */
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }
    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }
    public function buscaAlmacenados($query)
    {
        return $query->where('estado', 'almacenado')->whereNull('maquina_id');
    }

    public function buscaConsumiendo($query)
    {
        return $query->where('estado', 'consumiendo')->whereNotNull('maquina_id');
    }
    public function consumidoPor()
    {
        return $this->belongsTo(User::class, 'consumido_by');
    }
    // Relación con los elementos que están asociados a este producto
    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'producto_id');
    }
    public function elementos2()
    {
        return $this->hasMany(Elemento::class, 'producto_id_2');
    }

    public function elementos3()
    {
        return $this->hasMany(Elemento::class, 'producto_id_3');
    }

    // Diámetro (mm)
    public function getDiametroMmAttribute()
    {
        return $this->diametro ? number_format($this->diametro) . ' mm' : 'No asignado';
    }
    public function getLongitudMetrosAttribute()
    {
        return $this->longitud ? number_format($this->longitud) . ' m' : 'No asignado';
    }
    public function productoBase()
    {
        return $this->belongsTo(ProductoBase::class);
    }
    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class);
    }

    public function colada()
    {
        return $this->belongsTo(Colada::class);
    }
}
