<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Representa una entidad/ensamblaje de una planilla.
 *
 * Las entidades son los elementos estructurales como pilares, vigas, punzonamientos,
 * etc. que se componen de barras longitudinales y estribos transversales.
 */
class PlanillaEntidad extends Model
{
    use HasFactory;

    protected $table = 'planilla_entidades';

    protected $fillable = [
        'planilla_id',
        'linea',
        'marca',
        'situacion',
        'cantidad',
        'miembros',
        'modelo',
        'cotas',
        'longitud_ensamblaje',
        'peso_total',
        'total_barras',
        'total_estribos',
        'composicion',
        'distribucion',
        'dibujo_data',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'miembros' => 'integer',
        'longitud_ensamblaje' => 'decimal:2',
        'peso_total' => 'decimal:2',
        'total_barras' => 'integer',
        'total_estribos' => 'integer',
        'composicion' => 'array',
        'distribucion' => 'array',
        'dibujo_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con la planilla.
     */
    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }

    /**
     * Relación con las etiquetas de ensamblaje.
     * Cada entidad puede tener múltiples etiquetas (una por cada unidad).
     */
    public function etiquetasEnsamblaje()
    {
        return $this->hasMany(EtiquetaEnsamblaje::class, 'planilla_entidad_id');
    }

    /**
     * Relación con los elementos que componen esta entidad.
     */
    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'planilla_entidad_id');
    }

    /**
     * Obtiene las barras de la composición.
     */
    public function getBarrasAttribute(): array
    {
        return $this->composicion['barras'] ?? [];
    }

    /**
     * Obtiene los estribos de la composición.
     */
    public function getEstribosAttribute(): array
    {
        return $this->composicion['estribos'] ?? [];
    }

    /**
     * Obtiene la armadura longitudinal de la distribución.
     */
    public function getArmaduraLongitudinalAttribute(): array
    {
        return $this->distribucion['armadura_longitudinal'] ?? [];
    }

    /**
     * Obtiene la armadura transversal de la distribución.
     */
    public function getArmaduraTransversalAttribute(): array
    {
        return $this->distribucion['armadura_transversal'] ?? [];
    }

    /**
     * Descripción completa de la entidad.
     */
    public function getDescripcionCompletaAttribute(): string
    {
        return trim("{$this->marca} - {$this->situacion}");
    }

    /**
     * Resumen textual de la distribución para mostrar en UI.
     */
    public function getResumenDistribucionAttribute(): string
    {
        $partes = [];

        // Barras
        if ($this->total_barras > 0) {
            $barras = $this->armadura_longitudinal;
            $diametros = array_unique(array_column($barras, 'diametro'));
            sort($diametros);
            $partes[] = $this->total_barras . ' barras (Ø' . implode(', Ø', $diametros) . ')';
        }

        // Estribos
        if ($this->total_estribos > 0) {
            $estribos = $this->armadura_transversal;
            if (!empty($estribos)) {
                $primer = $estribos[0];
                $sep = $primer['separacion_aprox_cm'] ?? 0;
                $partes[] = "{$primer['cantidad']} estribos Ø{$primer['diametro']}" . ($sep ? " c/{$sep}cm" : '');
            }
        }

        return implode(' + ', $partes) ?: 'Sin distribución';
    }

    /**
     * Relación con las órdenes de ensamblaje.
     */
    public function ordenesEnsamblaje()
    {
        return $this->hasMany(OrdenPlanillaEnsamblaje::class, 'planilla_entidad_id');
    }

    /**
     * Verifica si la entidad tiene todos sus elementos fabricados.
     * Una entidad está "lista" cuando todos sus elementos tienen estado 'fabricado' o 'completado'.
     */
    public function listaParaEnsamblaje(): bool
    {
        $totalElementos = $this->elementos()->count();

        // Si no tiene elementos asignados, no está lista
        if ($totalElementos === 0) {
            return false;
        }

        $elementosFabricados = $this->elementos()
            ->where('elaborado', 1)
            ->count();

        return $elementosFabricados >= $totalElementos;
    }

    /**
     * Obtiene el número de elementos fabricados vs total.
     */
    public function getElementosFabricadosAttribute(): array
    {
        $total = $this->elementos()->count();
        $fabricados = $this->elementos()
            ->where('elaborado', 1)
            ->count();

        return [
            'fabricados' => $fabricados,
            'total' => $total,
            'porcentaje' => $total > 0 ? round(($fabricados / $total) * 100, 1) : 0,
            'listo' => $total > 0 && $fabricados >= $total,
        ];
    }

    /**
     * Scope: Solo entidades listas para ensamblaje.
     */
    public function scopeListasParaEnsamblaje($query)
    {
        return $query->whereHas('elementos', function ($q) {
            $q->where('elaborado', 1);
        })->whereDoesntHave('elementos', function ($q) {
            $q->where('elaborado', 0);
        });
    }

    /**
     * Scope: Entidades con al menos un elemento fabricado.
     */
    public function scopeConElementosFabricados($query)
    {
        return $query->whereHas('elementos', function ($q) {
            $q->where('elaborado', 1);
        });
    }

    /**
     * Scope: Entidades sin orden de ensamblaje asignada.
     */
    public function scopeSinOrdenEnsamblaje($query, int $maquinaId)
    {
        return $query->whereDoesntHave('ordenesEnsamblaje', function ($q) use ($maquinaId) {
            $q->where('maquina_id', $maquinaId);
        });
    }

    /**
     * Calcula los requerimientos de elementos para esta entidad.
     *
     * Devuelve un array con los tipos de elementos necesarios (barras y estribos)
     * basándose en la composición definida, multiplicado por la cantidad de unidades.
     *
     * @return array Estructura con requerimientos de barras y estribos
     */
    public function calcularRequerimientos(): array
    {
        $cantidad = $this->cantidad ?? 1;
        $composicion = $this->composicion ?? [];

        $requerimientos = [
            'cantidad_unidades' => $cantidad,
            'barras' => [],
            'estribos' => [],
            'totales' => [
                'barras' => 0,
                'estribos' => 0,
                'peso_total' => 0,
            ],
        ];

        // Procesar barras requeridas
        foreach ($composicion['barras'] ?? [] as $barra) {
            $cantidadPorUnidad = (int)($barra['cantidad'] ?? 1);
            $cantidadTotal = $cantidadPorUnidad * $cantidad;

            $requerimientos['barras'][] = [
                'diametro' => (int)($barra['diametro'] ?? 0),
                'longitud' => (float)($barra['longitud'] ?? 0),
                'dimensiones' => $barra['dimensiones'] ?? null,
                'figura' => $barra['figura'] ?? null,
                'peso_unitario' => (float)($barra['peso'] ?? 0),
                'cantidad_por_unidad' => $cantidadPorUnidad,
                'cantidad_total' => $cantidadTotal,
                'peso_total' => (float)($barra['peso'] ?? 0) * $cantidadTotal,
            ];

            $requerimientos['totales']['barras'] += $cantidadTotal;
            $requerimientos['totales']['peso_total'] += (float)($barra['peso'] ?? 0) * $cantidadTotal;
        }

        // Procesar estribos requeridos
        foreach ($composicion['estribos'] ?? [] as $estribo) {
            $cantidadPorUnidad = (int)($estribo['cantidad'] ?? 1);
            $cantidadTotal = $cantidadPorUnidad * $cantidad;

            $requerimientos['estribos'][] = [
                'diametro' => (int)($estribo['diametro'] ?? 0),
                'longitud' => (float)($estribo['longitud'] ?? 0),
                'dimensiones' => $estribo['dimensiones'] ?? null,
                'figura' => $estribo['figura'] ?? null,
                'dobleces' => (int)($estribo['dobleces'] ?? 0),
                'peso_unitario' => (float)($estribo['peso'] ?? 0),
                'cantidad_por_unidad' => $cantidadPorUnidad,
                'cantidad_total' => $cantidadTotal,
                'peso_total' => (float)($estribo['peso'] ?? 0) * $cantidadTotal,
            ];

            $requerimientos['totales']['estribos'] += $cantidadTotal;
            $requerimientos['totales']['peso_total'] += (float)($estribo['peso'] ?? 0) * $cantidadTotal;
        }

        return $requerimientos;
    }

    /**
     * Calcula los requerimientos para UNA SOLA etiqueta de ensamblaje.
     *
     * Útil para saber exactamente qué elementos necesita cada etiqueta individual.
     *
     * @return array Estructura con requerimientos de barras y estribos para una unidad
     */
    public function calcularRequerimientosPorEtiqueta(): array
    {
        $composicion = $this->composicion ?? [];

        $requerimientos = [
            'barras' => [],
            'estribos' => [],
            'totales' => [
                'barras' => 0,
                'estribos' => 0,
                'peso_total' => 0,
            ],
        ];

        // Procesar barras requeridas (por unidad)
        foreach ($composicion['barras'] ?? [] as $barra) {
            $cantidad = (int)($barra['cantidad'] ?? 1);

            $requerimientos['barras'][] = [
                'diametro' => (int)($barra['diametro'] ?? 0),
                'longitud' => (float)($barra['longitud'] ?? 0),
                'dimensiones' => $barra['dimensiones'] ?? null,
                'figura' => $barra['figura'] ?? null,
                'peso_unitario' => (float)($barra['peso'] ?? 0),
                'cantidad' => $cantidad,
                'peso_total' => (float)($barra['peso'] ?? 0) * $cantidad,
            ];

            $requerimientos['totales']['barras'] += $cantidad;
            $requerimientos['totales']['peso_total'] += (float)($barra['peso'] ?? 0) * $cantidad;
        }

        // Procesar estribos requeridos (por unidad)
        foreach ($composicion['estribos'] ?? [] as $estribo) {
            $cantidad = (int)($estribo['cantidad'] ?? 1);

            $requerimientos['estribos'][] = [
                'diametro' => (int)($estribo['diametro'] ?? 0),
                'longitud' => (float)($estribo['longitud'] ?? 0),
                'dimensiones' => $estribo['dimensiones'] ?? null,
                'figura' => $estribo['figura'] ?? null,
                'dobleces' => (int)($estribo['dobleces'] ?? 0),
                'peso_unitario' => (float)($estribo['peso'] ?? 0),
                'cantidad' => $cantidad,
                'peso_total' => (float)($estribo['peso'] ?? 0) * $cantidad,
            ];

            $requerimientos['totales']['estribos'] += $cantidad;
            $requerimientos['totales']['peso_total'] += (float)($estribo['peso'] ?? 0) * $cantidad;
        }

        return $requerimientos;
    }

    /**
     * Compara los requerimientos teóricos con los elementos disponibles.
     *
     * Agrupa los elementos vinculados a esta entidad por características
     * y los compara con los requerimientos de la composición.
     *
     * @return array Comparativa de requerido vs disponible
     */
    public function compararRequerimientosConDisponibles(): array
    {
        $requerimientos = $this->calcularRequerimientos();

        // Obtener elementos vinculados a esta entidad agrupados por características
        $elementosDisponibles = $this->elementos()
            ->select('diametro', 'longitud', 'dimensiones', 'figura', 'peso', 'elaborado')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('SUM(CASE WHEN elaborado = 1 THEN 1 ELSE 0 END) as fabricados')
            ->selectRaw('SUM(CASE WHEN elaborado = 0 THEN 1 ELSE 0 END) as pendientes')
            ->selectRaw('0 as fabricando')
            ->groupBy('diametro', 'longitud', 'dimensiones', 'figura', 'peso')
            ->get();

        // Crear mapa de disponibles por clave (diametro|longitud|dimensiones)
        $mapaDisponibles = [];
        foreach ($elementosDisponibles as $elem) {
            $clave = $this->generarClaveElemento($elem->diametro, $elem->longitud, $elem->dimensiones);
            $mapaDisponibles[$clave] = [
                'total' => $elem->cantidad,
                'fabricados' => $elem->fabricados,
                'pendientes' => $elem->pendientes,
                'fabricando' => $elem->fabricando,
                'peso' => $elem->peso,
            ];
        }

        // Comparar barras
        $comparativaBarras = [];
        foreach ($requerimientos['barras'] as $barra) {
            $clave = $this->generarClaveElemento($barra['diametro'], $barra['longitud'], $barra['dimensiones']);
            $disponible = $mapaDisponibles[$clave] ?? null;

            $comparativaBarras[] = [
                'diametro' => $barra['diametro'],
                'longitud' => $barra['longitud'],
                'dimensiones' => $barra['dimensiones'],
                'figura' => $barra['figura'],
                'requerido' => $barra['cantidad_total'],
                'disponible_total' => $disponible['total'] ?? 0,
                'fabricados' => $disponible['fabricados'] ?? 0,
                'pendientes' => $disponible['pendientes'] ?? 0,
                'fabricando' => $disponible['fabricando'] ?? 0,
                'faltantes' => max(0, $barra['cantidad_total'] - ($disponible['total'] ?? 0)),
                'listos_para_ensamblaje' => $disponible['fabricados'] ?? 0,
                'completo' => ($disponible['fabricados'] ?? 0) >= $barra['cantidad_total'],
            ];
        }

        // Comparar estribos
        $comparativaEstribos = [];
        foreach ($requerimientos['estribos'] as $estribo) {
            $clave = $this->generarClaveElemento($estribo['diametro'], $estribo['longitud'], $estribo['dimensiones']);
            $disponible = $mapaDisponibles[$clave] ?? null;

            $comparativaEstribos[] = [
                'diametro' => $estribo['diametro'],
                'longitud' => $estribo['longitud'],
                'dimensiones' => $estribo['dimensiones'],
                'figura' => $estribo['figura'],
                'dobleces' => $estribo['dobleces'],
                'requerido' => $estribo['cantidad_total'],
                'disponible_total' => $disponible['total'] ?? 0,
                'fabricados' => $disponible['fabricados'] ?? 0,
                'pendientes' => $disponible['pendientes'] ?? 0,
                'fabricando' => $disponible['fabricando'] ?? 0,
                'faltantes' => max(0, $estribo['cantidad_total'] - ($disponible['total'] ?? 0)),
                'listos_para_ensamblaje' => $disponible['fabricados'] ?? 0,
                'completo' => ($disponible['fabricados'] ?? 0) >= $estribo['cantidad_total'],
            ];
        }

        // Calcular resumen
        $totalRequerido = $requerimientos['totales']['barras'] + $requerimientos['totales']['estribos'];
        $totalFabricados = array_sum(array_column($comparativaBarras, 'fabricados'))
            + array_sum(array_column($comparativaEstribos, 'fabricados'));

        return [
            'entidad_id' => $this->id,
            'marca' => $this->marca,
            'situacion' => $this->situacion,
            'cantidad_unidades' => $this->cantidad,
            'barras' => $comparativaBarras,
            'estribos' => $comparativaEstribos,
            'resumen' => [
                'total_requerido' => $totalRequerido,
                'total_fabricados' => $totalFabricados,
                'porcentaje_completado' => $totalRequerido > 0
                    ? round(($totalFabricados / $totalRequerido) * 100, 1)
                    : 0,
                'listo_para_ensamblaje' => $totalFabricados >= $totalRequerido,
            ],
        ];
    }

    /**
     * Genera una clave única para identificar un tipo de elemento.
     */
    protected function generarClaveElemento(?int $diametro, ?float $longitud, ?string $dimensiones): string
    {
        return sprintf('%d|%.0f|%s', $diametro ?? 0, $longitud ?? 0, $dimensiones ?? '');
    }

    /**
     * Calcula cuántas etiquetas de ensamblaje pueden completarse con los elementos fabricados.
     *
     * @return array Información sobre etiquetas completables
     */
    public function calcularEtiquetasCompletables(): array
    {
        $requerimientosPorEtiqueta = $this->calcularRequerimientosPorEtiqueta();
        $comparativa = $this->compararRequerimientosConDisponibles();

        // Calcular cuántas etiquetas completas se pueden hacer
        $minEtiquetasCompletables = $this->cantidad; // Empezar con el máximo posible

        foreach ($comparativa['barras'] as $barra) {
            $cantidadPorEtiqueta = 0;
            foreach ($requerimientosPorEtiqueta['barras'] as $req) {
                if ($req['diametro'] == $barra['diametro'] && abs($req['longitud'] - $barra['longitud']) < 1) {
                    $cantidadPorEtiqueta = $req['cantidad'];
                    break;
                }
            }

            if ($cantidadPorEtiqueta > 0) {
                $etiquetasPosibles = floor($barra['fabricados'] / $cantidadPorEtiqueta);
                $minEtiquetasCompletables = min($minEtiquetasCompletables, $etiquetasPosibles);
            }
        }

        foreach ($comparativa['estribos'] as $estribo) {
            $cantidadPorEtiqueta = 0;
            foreach ($requerimientosPorEtiqueta['estribos'] as $req) {
                if ($req['diametro'] == $estribo['diametro'] && abs($req['longitud'] - $estribo['longitud']) < 1) {
                    $cantidadPorEtiqueta = $req['cantidad'];
                    break;
                }
            }

            if ($cantidadPorEtiqueta > 0) {
                $etiquetasPosibles = floor($estribo['fabricados'] / $cantidadPorEtiqueta);
                $minEtiquetasCompletables = min($minEtiquetasCompletables, $etiquetasPosibles);
            }
        }

        return [
            'total_etiquetas' => $this->cantidad,
            'etiquetas_completables' => max(0, (int)$minEtiquetasCompletables),
            'etiquetas_pendientes' => $this->cantidad - max(0, (int)$minEtiquetasCompletables),
            'porcentaje' => $this->cantidad > 0
                ? round((max(0, (int)$minEtiquetasCompletables) / $this->cantidad) * 100, 1)
                : 0,
        ];
    }
}
