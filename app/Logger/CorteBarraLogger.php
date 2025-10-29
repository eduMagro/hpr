<?php

namespace App\Logger;

use Illuminate\Support\Facades\Storage;

class CorteBarraLogger
{
    protected string $path = 'cortes/cortes-barra.csv';

    // 🧷 Cabecera fija
    protected array $headers = [
        'timestamp',
        'Operario',
        'Cód. Planilla',
        'Cód. Etiqueta',
        'Cód. Elemento',
        'Máquina',
        'Materia prima',
        'Diametro',
        'Longitud pieza (m)',
        'Longitud barra (m)',
        'Piezas/barra',
        'Piezas fabricadas',
        'Barras usadas',
        'Patrón',
        'Sobrante',
        'comentario',
    ];

    public function registrar(array $data): void
    {
        // Crear archivo con cabecera si no existe
        if (!Storage::exists($this->path)) {
            Storage::put($this->path, implode(',', $this->headers) . "\n");
        }

        // Respetar el orden y campos fijos
        $linea = implode(',', array_map(
            [$this, 'escapar'],
            array_map(fn($key) => $data[$key] ?? '', $this->headers)
        )) . "\n";

        Storage::append($this->path, $linea);
    }

    private function escapar($valor): string
    {
        $valor = (string) $valor;
        if (str_contains($valor, ',') || str_contains($valor, "\n") || str_contains($valor, '"')) {
            $valor = '"' . str_replace('"', '""', $valor) . '"';
        }
        return $valor;
    }
}
