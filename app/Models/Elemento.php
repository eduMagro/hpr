<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Elemento extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'elementos';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'conjunto_id',
        'nombre',
        'cantidad',
        'diametro',
        'longitud',
        'peso',
    ];

    /**
     * Indica si el modelo debe gestionar las marcas de tiempo.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Obtiene el conjunto al que pertenece el elemento.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function conjunto()
    {
        return $this->belongsTo(Conjunto::class, 'conjunto_id', 'id');
    }

    /**
     * Definir cualquier casting de atributos si es necesario.
     *
     * @var array
     */
    protected $casts = [
        'cantidad' => 'integer',
        'diametro' => 'decimal:2',
        'longitud' => 'decimal:2',
        'peso' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
