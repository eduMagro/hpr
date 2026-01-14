<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrecioMaterialConfig extends Model
{
    protected $table = 'precios_material_config';

    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
    ];

    /**
     * Obtiene un valor de configuración.
     */
    public static function get(string $clave, $default = null)
    {
        $config = static::where('clave', $clave)->first();
        return $config ? $config->valor : $default;
    }

    /**
     * Establece un valor de configuración.
     */
    public static function set(string $clave, string $valor, ?string $descripcion = null): void
    {
        static::updateOrCreate(
            ['clave' => $clave],
            ['valor' => $valor, 'descripcion' => $descripcion]
        );
    }

    /**
     * Obtiene el producto base de referencia (por defecto Ø16 a 12m).
     */
    public static function getProductoBaseReferencia(): ?ProductoBase
    {
        $id = static::get('producto_base_referencia_id');

        if ($id) {
            return ProductoBase::find($id);
        }

        // Por defecto buscar Ø16 a 12m
        return ProductoBase::where('diametro', 16)
            ->where('longitud', 12)
            ->first();
    }

    /**
     * Obtiene el fabricante marcado como Siderúrgica Sevillana.
     */
    public static function getFabricanteSiderurgica(): ?Fabricante
    {
        return Fabricante::where('es_siderurgica_sevillana', true)->first();
    }
}
