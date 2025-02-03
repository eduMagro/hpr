<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subpaquete extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'subpaquetes';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'elemento_id',
		'planilla_id',
		'paquete_id',
        'nombre',
        'peso',
        'dimensiones',
        'cantidad',
        'descripcion'
    ];

    /**
     * Indica si el modelo debe gestionar las marcas de tiempo.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Relación con el modelo Elemento (un subpaquete pertenece a un elemento).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function elemento()
    {
        return $this->belongsTo(Elemento::class, 'elemento_id');
    }

      /**
     * Relación con el modelo Planilla (un subpaquete pertenece a una planilla).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }
	 public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }
}
