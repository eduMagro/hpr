<?php

namespace App\Services\Fabricantes;

use Illuminate\Support\Str;

class FabricanteServiceFactory
{
    public static function getService(string $fabricante): FabricanteServiceInterface
    {
        $fabricante = trim($fabricante); // Remover espacios al inicio y final
        $fabricante = preg_replace('/\s+/', ' ', $fabricante); // Remover espacios múltiples

        // Convertir el nombre del fabricante a PascalCase
        $fabricante = Str::studly(strtolower($fabricante));
        $serviceClass = "App\\Services\\Fabricantes\\Fabricante{$fabricante}Service";
        // Verificar si la clase existe
        if (!class_exists($serviceClass)) {
            throw new \Exception("No existe un servicio para el fabricante: {$fabricante}");
        }

        // Resolver la clase desde el contenedor de Laravel
        return app($serviceClass);
    }
}
