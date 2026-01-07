<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;

    protected $fillable = ['clave', 'valor', 'descripcion'];

    public static function get(string $key, $default = null)
    {
        $config = self::where('clave', $key)->first();
        if (!$config) {
            return $default;
        }

        // Try to decode JSON
        $decoded = json_decode($config->valor, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $config->valor;
    }

    public static function set(string $key, $value, $descripcion = null)
    {
        $encoded = is_array($value) || is_object($value) ? json_encode($value) : $value;

        return self::updateOrCreate(
            ['clave' => $key],
            [
                'valor' => $encoded,
                'descripcion' => $descripcion
            ]
        );
    }
}
