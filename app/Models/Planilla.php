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
<<<<<<< HEAD
=======
		'users_id',
>>>>>>> 6fea693 (primercommit)
        'cod_obra',
        'cliente',
        'nom_obra',
        'seccion',
        'descripcion',
<<<<<<< HEAD
        'poblacion',
        'codigo', // Asegúrate de que este campo esté aquí
        'peso_total',
=======
        'ensamblado',
        'codigo', // Asegúrate de que este campo esté aquí
        'peso_total',
		'fecha_inicio',
		'fecha_finalizacion',
		'tiempo_fabricacion',
		
>>>>>>> 6fea693 (primercommit)
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
<<<<<<< HEAD
    public function conjuntos()
    {
        return $this->hasMany(Conjunto::class, 'planilla_id', 'id');
=======
public function elementos()
{
    return $this->hasMany(Elemento::class, 'planilla_id');
}

	    /**
     * Relación con la tabla 'users'
     * Una planilla pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');  // Relación con User
>>>>>>> 6fea693 (primercommit)
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
