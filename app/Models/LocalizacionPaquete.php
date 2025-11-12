<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo LocalizacionPaquete
 * 
 * Representa la ubicación de un paquete en el mapa visual del almacén.
 * Cada paquete puede tener una única localización definida por coordenadas (x1,y1) a (x2,y2)
 * donde cada celda representa 0.5 metros del espacio físico real.
 */
class LocalizacionPaquete extends Model
{
    // Nombre de la tabla en la base de datos
    protected $table = 'localizaciones_paquetes';

    // Desactivar timestamps si la tabla no los tiene
    // Si tu tabla SÍ tiene created_at y updated_at, cambia esto a true
    public $timestamps = true;

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'paquete_id',  // ID del paquete al que pertenece esta localización
        'x1',          // Coordenada X inicial (columna inicial en el grid)
        'y1',          // Coordenada Y inicial (fila inicial en el grid)
        'x2',          // Coordenada X final (columna final en el grid)
        'y2',          // Coordenada Y final (fila final en el grid)
    ];

    // ==================== RELACIONES ====================

    /**
     * Relación: Una localización pertenece a un paquete
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }

    // ==================== ACCESSORS ====================

    /**
     * Accessor: Obtiene el ancho de la localización en celdas
     * 
     * @return int Número de celdas de ancho
     */
    public function getAnchoCeldasAttribute()
    {
        return $this->x2 - $this->x1 + 1;
    }

    /**
     * Accessor: Obtiene el alto de la localización en celdas
     * 
     * @return int Número de celdas de alto
     */
    public function getAltoCeldasAttribute()
    {
        return $this->y2 - $this->y1 + 1;
    }

    /**
     * Accessor: Obtiene el ancho real en metros
     * Cada celda = 0.5 metros
     * 
     * @return float Ancho en metros
     */
    public function getAnchoMetrosAttribute()
    {
        return $this->ancho_celdas * 0.5;
    }

    /**
     * Accessor: Obtiene el alto real en metros
     * Cada celda = 0.5 metros
     * 
     * @return float Alto en metros
     */
    public function getAltoMetrosAttribute()
    {
        return $this->alto_celdas * 0.5;
    }

    /**
     * Accessor: Obtiene el área ocupada en metros cuadrados
     * 
     * @return float Área en m²
     */
    public function getAreaM2Attribute()
    {
        return $this->ancho_metros * $this->alto_metros;
    }

    // ==================== MÉTODOS ÚTILES ====================

    /**
     * Verifica si esta localización solapa con otra
     * 
     * @param int $x1 Coordenada X1 de la otra localización
     * @param int $y1 Coordenada Y1 de la otra localización
     * @param int $x2 Coordenada X2 de la otra localización
     * @param int $y2 Coordenada Y2 de la otra localización
     * @return bool True si hay solape, false si no
     */
    public function solapaCon($x1, $y1, $x2, $y2)
    {
        return !($this->x2 < $x1 || $this->x1 > $x2 || $this->y2 < $y1 || $this->y1 > $y2);
    }

    /**
     * Obtiene las coordenadas centrales de la localización
     * 
     * @return array ['x' => int, 'y' => int]
     */
    public function getCentro()
    {
        return [
            'x' => (int) (($this->x1 + $this->x2) / 2),
            'y' => (int) (($this->y1 + $this->y2) / 2),
        ];
    }

    /**
     * Verifica si las coordenadas están dentro de esta localización
     * 
     * @param int $x Coordenada X
     * @param int $y Coordenada Y
     * @return bool True si el punto está dentro
     */
    public function contieneCoord($x, $y)
    {
        return ($x >= $this->x1 && $x <= $this->x2 && $y >= $this->y1 && $y <= $this->y2);
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Filtra localizaciones que solapen con un área específica
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSolapaConArea($query, $x1, $y1, $x2, $y2)
    {
        return $query->where(function ($q) use ($x1, $y1, $x2, $y2) {
            $q->where('x1', '<=', $x2)
                ->where('x2', '>=', $x1)
                ->where('y1', '<=', $y2)
                ->where('y2', '>=', $y1);
        });
    }

    /**
     * Scope: Filtra localizaciones dentro de un rango de área (en m²)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minArea Área mínima en m²
     * @param float $maxArea Área máxima en m²
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConAreaEntre($query, $minArea, $maxArea)
    {
        // Convertir m² a celdas (1 celda = 0.25 m²)
        $minCeldas = $minArea / 0.25;
        $maxCeldas = $maxArea / 0.25;

        return $query->whereRaw('((x2 - x1 + 1) * (y2 - y1 + 1)) BETWEEN ? AND ?', [$minCeldas, $maxCeldas]);
    }

    // ==================== MÉTODOS ESTÁTICOS ====================

    /**
     * Busca localizaciones que solapen con las coordenadas dadas
     * Excluye opcionalmente una localización específica
     * 
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * @param int|null $excluirId ID de localización a excluir
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function buscarSolapes($x1, $y1, $x2, $y2, $excluirId = null)
    {
        $query = self::solapaConArea($x1, $y1, $x2, $y2);

        if ($excluirId) {
            $query->where('id', '!=', $excluirId);
        }

        return $query->get();
    }
}
