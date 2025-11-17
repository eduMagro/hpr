<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Paquete
 * Representa un paquete/conjunto de elementos (barras o estribos) en el almacén
 */
class Paquete extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'paquetes';

    protected $fillable = [
        'codigo',
        'nave_id',
        'planilla_id',
        'ubicacion_id',
        'peso',
        'estado',
    ];

    // ==================== MÉTODOS ESTÁTICOS ====================

    /**
     * Genera un código único para un paquete con el formato: PYYMMnnnn
     * Donde:
     * - P = Prefijo para paquete
     * - YY = Año (2 dígitos)
     * - MM = Mes (2 dígitos)
     * - nnnn = Número secuencial (4 dígitos con padding)
     * 
     * @return string Código generado
     */
    public static function generarCodigo()
    {
        $year = now()->format('y');
        $month = now()->format('m');

        $ultimoCodigo = self::withTrashed()   // 👈 INCLUYE soft-deletes
            ->where('codigo', 'LIKE', "P{$year}{$month}%")
            ->orderBy('codigo', 'desc')
            ->value('codigo');

        if ($ultimoCodigo) {
            $ultimoNumero = intval(substr($ultimoCodigo, 5));
            $nuevoNumero = str_pad($ultimoNumero + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nuevoNumero = '0001';
        }

        return "P{$year}{$month}{$nuevoNumero}";
    }

    // ==================== RELACIONES ====================

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
     * Relación: Un paquete puede estar asociado a muchas salidas
     * (Relación muchos a muchos a través de tabla pivote salidas_paquetes)
     */
    public function salidas()
    {
        return $this->belongsToMany(Salida::class, 'salidas_paquetes', 'paquete_id', 'salida_id');
    }

    /**
     * Relación: Un paquete puede estar en muchas salidas
     * (Acceso directo a la tabla pivote)
     */

    /**
     * Relación: Obtiene la salida principal del paquete (la primera asociada).
     * Útil para cuando el paquete tiene una única salida o queremos la principal.
     */
    public function salida()
    {
        return $this->belongsToMany(Salida::class, 'salidas_paquetes', 'paquete_id', 'salida_id')->limit(1);
    }

    public function salidasPaquetes()
    {
        return $this->hasMany(SalidaPaquete::class, 'paquete_id');
    }

    // ==================== ACCESSORS ====================

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
     * Accessor: Obtiene el ID de la salida principal del paquete.
     * Retorna null si no tiene salida asignada.
     */
    public function getSalidaIdAttribute()
    {
        // Primero intentar obtener desde la relación si ya está cargada
        if ($this->relationLoaded('salida')) {
            $salida = $this->salida->first();
            return $salida ? $salida->id : null;
        }

        // Si no está cargada, hacer una consulta directa
        return \DB::table('salidas_paquetes')
            ->where('paquete_id', $this->id)
            ->value('salida_id');
    }

    /**
     * Calcula el tamaño del paquete basándose en las longitudes máximas
     * de cada elemento (extraídas del campo dimensiones de cada elemento).
     * El ancho es fijo: 1 metro.
     * Las dimensiones almacenadas están en cm, convertimos a metros.
     * 
     * @return array ['ancho' => float, 'longitud' => float] en metros
     */
    public function getTamañoAttribute()
    {
        $maxLongitudCm = 0;

        foreach ($this->etiquetas as $etiqueta) {
            foreach ($etiqueta->elementos as $elemento) {
                $maxLongitudElemento = $this->extraerMaxLongitudDeDimensiones($elemento->dimensiones);

                if ($maxLongitudElemento > $maxLongitudCm) {
                    $maxLongitudCm = $maxLongitudElemento;
                }
            }
        }

        // ✅ Convertimos de cm a metros (corregido)
        $maxLongitudM = $maxLongitudCm / 100;

        return [
            'ancho'    => 1,
            'longitud' => $maxLongitudM
        ];
    }

    // ==================== MÉTODOS ÚTILES ====================

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

    // ==================== MÉTODOS PRIVADOS ====================

    /**
     * Extrae la longitud máxima de un string de dimensiones en cm.
     * Ejemplo: "10 90d 200 90d" => devuelve 200 (cm).
     * 
     * @param string|null $dimensiones String con las dimensiones
     * @return float Longitud máxima en cm
     */
    protected function extraerMaxLongitudDeDimensiones($dimensiones)
    {
        if (!$dimensiones) return 0;

        $partes = preg_split('/\s+/', trim($dimensiones));
        $max = 0;

        foreach ($partes as $parte) {
            // ignoramos las partes que contengan 'd' (grados)
            if (strpos($parte, 'd') === false && is_numeric($parte)) {
                $valor = floatval($parte);
                if ($valor > $max) {
                    $max = $valor;
                }
            }
        }

        return $max; // en cm
    }

    /**
     * Método privado: Determina si un elemento es un estribo
     * 
     * @param object $elemento Elemento a evaluar
     * @return bool True si es estribo, false si no
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
