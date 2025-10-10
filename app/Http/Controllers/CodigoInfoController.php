<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Paquete;

class CodigoInfoController extends Controller
{
    public function show(Request $request)
    {
        $code = strtoupper(trim((string)$request->query('code', '')));

        if ($code === '' || strlen($code) < 2) {
            return response()->json(['ok' => false, 'error' => 'Código vacío o inválido'], 422);
        }

        // Producto MP********
        if (str_starts_with($code, 'MP')) {
            $prod = Producto::with('productoBase', 'ubicacion')->where('codigo', $code)->first();
            if (!$prod) {
                return response()->json(['ok' => false, 'error' => 'Producto no encontrado'], 404);
            }

            $tipoBase = strtolower($prod->productoBase->tipo ?? '');
            $sigla = match ($tipoBase) {
                'barra'       => 'B',
                'encarretado' => 'E',
                default       => mb_strtoupper(mb_substr($tipoBase, 0, 1)),
            };

            return response()->json([
                'ok'        => true,
                'clase'     => 'producto',
                'codigo'    => $code,
                'sigla'     => $sigla,
                'tipo'      => $tipoBase,                          // barra | encarretado
                'diametro'  => (int) $prod->productoBase->diametro, // ← AQUÍ el Ø
                'longitud'  => $tipoBase === 'encarretado'
                    ? null
                    : ($prod->productoBase->longitud ?? null),
                'ubicacion' => $prod->ubicacion->nombre ?? null,
            ]);
        }

        // Paquete P*********
        if (str_starts_with($code, 'P')) {
            $paq = Paquete::with('ubicacion')->where('codigo', $code)->first();
            if (!$paq) {
                return response()->json(['ok' => false, 'error' => 'Paquete no encontrado'], 404);
            }

            return response()->json([
                'ok'        => true,
                'clase'     => 'paquete',
                'codigo'    => $code,
                'sigla'     => 'PAQ',
                'tipo'      => 'paquete',
                'diametro'  => null,
                'longitud'  => null,
                'ubicacion' => $paq->ubicacion->nombre ?? null,
            ]);
        }

        return response()->json(['ok' => false, 'error' => 'Prefijo no soportado (usa MP o P)'], 422);
    }
}
