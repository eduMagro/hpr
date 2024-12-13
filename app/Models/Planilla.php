<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planilla extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'planillas';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'cod_obra',
        'cliente',
        'nom_obra',
        'seccion',
        'descripcion',
        'poblacion',
        'codigo', // Asegúrate de que este campo esté aquí
        'peso_total',
    ];

    /**
     * Indica si el modelo debe gestionar las marcas de tiempo.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Obtiene los conjuntos asociados a la planilla.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function conjuntos()
    {
        return $this->hasMany(Conjunto::class, 'planilla_id', 'id');
    }

    /**
     * Definir cualquier casting de atributos si es necesario.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
