<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \App\Models\Categoria;

class Nomina extends Model
{
    protected $table = 'nominas';
    protected $casts = [
        'fecha' => 'date',
    ];

    protected $fillable = [
        'empleado_id',
        'categoria_id',
        'dias_trabajados',
        'salario_base',
        'plus_actividad',
        'prorrateo',
        'plus_varios',
        'horas_extra',
        'valor_hora_extra',
        'total_devengado',
        'total_deducciones_ss',
        'irpf_mensual',
        'liquido',
        'bruto_anual_estimado',
        'base_irpf_previa',
        'cuota_irpf_anual_sin_minimo',
        'cuota_minimo_personal',
        'cuota_irpf_anual',
        'fecha'
    ];

    public function empleado()
    {
        return $this->belongsTo(\App\Models\User::class, 'empleado_id');
    }

    public function categoria()
    {
        return $this->belongsTo(\App\Models\Categoria::class, 'categoria_id');
    }
}
