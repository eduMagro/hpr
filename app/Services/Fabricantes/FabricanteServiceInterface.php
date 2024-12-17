<?php
namespace App\Services\Fabricantes;

use Illuminate\Http\Request;

interface FabricanteServiceInterface
{
    public function procesar(Request $request): void;
}
