<?php
namespace App\Services\Fabricantes;

use Illuminate\Support\Str;

class FabricanteServiceFactory
{
    public static function getService(string $fabricante): FabricanteServiceInterface
    {
        // Usar el nombre del fabricante para determinar el servicio adecuado
        $fabricante = Str::studly($fabricante); // Convertir a formato PascalCase
        $serviceClass = "App\\Services\\Fabricantes\\{$fabricante}Service";

        if (!class_exists($serviceClass)) {
            throw new \Exception("No existe un servicio para el fabricante: {$fabricante}");
        }

        return app($serviceClass); // Resolver la clase con el contenedor de Laravel
    }
}
