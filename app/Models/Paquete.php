<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Paquete
 * Representa un paquete/conjunto de elementos (barras o estribos) en el almacén
 */
class Paquete extends Model
{
    protected $table = 'paquetes';

    protected $fillable = [
        'codigo',
        'nave_id',
        'planilla_id',
        'ubicacion_id',
        'peso',
        'subido',
    ];

    /**
     * Relación: Un paquete pertenece a una nave (obra)
     */
    public function nave()
    {
        return $this->belongsTo(Obra::class, 'nave_id');
    }

    /**
     * Relación: Un paquete pertenece a una planilla
     */
    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }

    /**
     * Relación: Un paquete pertenece a una ubicación física
     */
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }

    /**
     * Relación: Un paquete tiene UNA localización en el mapa (coordenadas x1,y1,x2,y2)
     * Esta relación permite saber dónde está posicionado el paquete en el mapa visual
     */
    public function localizacionPaquete()
    {
        return $this->hasOne(LocalizacionPaquete::class, 'paquete_id');
    }

    /**
     * Relación: Un paquete tiene muchas etiquetas
     * Las etiquetas contienen los elementos (barras/estribos) que componen el paquete
     */
    public function etiquetas()
    {
        return $this->hasMany(Etiqueta::class, 'paquete_id');
    }

    /**
     * Relación: Un paquete tiene muchos subpaquetes
     */
    public function subpaquetes()
    {
        return $this->hasMany(Subpaquete::class, 'paquete_id');
    }

    /**
     * Relación: Un paquete puede estar en muchas salidas
     */
    public function salidasPaquetes()
    {
        return $this->hasMany(SalidaPaquete::class, 'paquete_id');
    }

    /**
     * Accessor: Obtiene las dimensiones del paquete en celdas
     * Si tiene localización, retorna ancho y alto basado en coordenadas
     */
    public function getDimensionesAttribute()
    {
        if ($this->localizacionPaquete) {
            $loc = $this->localizacionPaquete;
            return [
                'ancho_celdas' => $loc->x2 - $loc->x1 + 1,
                'alto_celdas'  => $loc->y2 - $loc->y1 + 1,
            ];
        }
        return null;
    }

    /**
     * Método: Verifica si el paquete tiene localización asignada
     */
    public function tieneLocalizacion()
    {
        return $this->localizacionPaquete()->exists();
    }

    /**
     * Método: Obtiene el tipo de contenido del paquete (barras, estribos o mixto)
     * Analiza los elementos de todas sus etiquetas
     */
    public function getTipoContenido()
    {
        $tieneBarras = false;
        $tieneEstribos = false;

        foreach ($this->etiquetas as $etiqueta) {
            foreach ($etiqueta->elementos as $elemento) {
                // Detectar por figura o dimensiones
                if ($this->esEstribo($elemento)) {
                    $tieneEstribos = true;
                } else {
                    $tieneBarras = true;
                }
            }
        }

        if ($tieneBarras && !$tieneEstribos) return 'barras';
        if ($tieneEstribos && !$tieneBarras) return 'estribos';
        return 'mixto';
    }

    /**
     * Método privado: Determina si un elemento es un estribo
     */
    private function esEstribo($elemento)
    {
        if (isset($elemento->figura)) {
            $figura = strtolower($elemento->figura);
            return in_array($figura, ['estribo', 'cerco', 'u', 'l']);
        }

        // Alternativa por longitud (estribos suelen ser < 3m)
        if (isset($elemento->longitud)) {
            return (float) $elemento->longitud < 3.0;
        }

        return false;
    }
}