<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Colada extends Model
{
    protected $table = 'coladas';

    protected $fillable = [
        'numero_colada',
        'producto_base_id',
        'fabricante_id',
        'documento',
        'codigo_adherencia',
        'observaciones',
        'dio_de_alta',
        'ultima_modificacion',
    ];

    protected static function booted()
    {
        static::updated(function ($colada) {
            $cambios = $colada->getDirty();

            if (empty($cambios)) {
                return;
            }

            $original = [];
            foreach ($cambios as $campo => $nuevoValor) {
                // No logueamos timestamps
                if (in_array($campo, ['updated_at']))
                    continue;
                $original[$campo] = $colada->getOriginal($campo);
            }

            if (empty($original))
                return;

            $logDir = storage_path('logs/modificaciones_coladas');
            if (!file_exists($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $nombreArchivo = 'coladas_log_' . date('m') . '-' . date('Y') . '.json';
            $logPath = $logDir . DIRECTORY_SEPARATOR . $nombreArchivo;

            $data = [
                'fecha' => date('Y-m-d H:i:s'),
                'colada_id' => $colada->id,
                'numero_colada' => $colada->numero_colada,
                'usuario_id' => auth()->id() ?? 'Sistema/API',
                'usuario_nombre' => auth()->user()->name ?? 'Sistema',
                'antes' => $original,
                'despues' => array_intersect_key($cambios, $original)
            ];

            $logsActuales = [];
            if (file_exists($logPath)) {
                $contenido = file_get_contents($logPath);
                $logsActuales = json_decode($contenido, true) ?: [];
            }

            $logsActuales[] = $data;

            file_put_contents($logPath, json_encode($logsActuales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        });
    }

    public function dioDeAltaPor()
    {
        return $this->belongsTo(User::class, 'dio_de_alta');
    }

    public function ultimoModificadoPor()
    {
        return $this->belongsTo(User::class, 'ultima_modificacion');
    }

    public function productoBase()
    {
        return $this->belongsTo(ProductoBase::class);
    }

    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function pedidoProductoColadas()
    {
        return $this->hasMany(PedidoProductoColada::class);
    }
}
