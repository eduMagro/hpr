<?php

namespace App\Logger;

use Illuminate\Support\Facades\Storage;

class CorteBarraLogger
{
    protected string $path = 'cortes/cortes-barra.csv';

    public function registrar(array $data): void
    {
        // Crear archivo con cabecera si no existe
        if (!Storage::exists($this->path)) {
            Storage::put($this->path, implode(',', array_keys($data)) . "\n");
        }

        // Añadir fila
        $linea = implode(',', array_map([$this, 'escapar'], $data)) . "\n";
        Storage::append($this->path, $linea);
    }

    private function escapar($valor): string
    {
        // Escapar valores que contienen comas, saltos de línea, etc.
        $valor = (string) $valor;
        if (str_contains($valor, ',') || str_contains($valor, "\n") || str_contains($valor, '"')) {
            $valor = '"' . str_replace('"', '""', $valor) . '"';
        }
        return $valor;
    }
}
