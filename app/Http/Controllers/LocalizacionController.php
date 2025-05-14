<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Localizacion;
use App\Models\Producto;
use App\Models\Paquete;
use App\Models\Maquina;


class LocalizacionController extends Controller
{
    //------------------------------------------------------------------------------------ INDEX()
    public function index()
    {
        $localizaciones = Localizacion::all();
        return view('localizaciones.index', compact('localizaciones'));
    }
    //------------------------------------------------------------------------------------ CREATE()
    public function create()
    {
        $localizaciones = Localizacion::all();
        return view('localizaciones.create', compact('localizaciones'));
    }
    //------------------------------------------------------------------------------------ VERIFICAR()
    public function verificar(Request $request)
    {
        $existe = Localizacion::where('x1', $request->x1)
            ->where('y1', $request->y1)
            ->where('x2', $request->x2)
            ->where('y2', $request->y2)
            ->first();

        if ($existe) {
            return response()->json([
                'existe' => true,
                'localizacion' => $existe
            ]);
        } else {
            return response()->json(['existe' => false]);
        }
    }

    //------------------------------------------------------------------------------------ STORE()
    public function store(Request $request)
    {
        $request->validate([
            'x1' => 'required|integer|min:1',
            'y1' => 'required|integer|min:1',
            'x2' => 'required|integer|min:1',
            'y2' => 'required|integer|min:1',
            'tipo' => 'required|in:material,maquina,transitable',
        ], [
            'x1.required' => 'La coordenada x1 es obligatoria.',
            'y1.required' => 'La coordenada y1 es obligatoria.',
            'x2.required' => 'La coordenada x2 es obligatoria.',
            'y2.required' => 'La coordenada y2 es obligatoria.',
            'tipo.required' => 'Debe indicar el tipo de localización.',
            'tipo.in' => 'El tipo debe ser material, maquina o transitable.',
        ]);

        // Asegurarse de que x1 <= x2 y y1 <= y2 (orden)
        $x1 = min($request->x1, $request->x2);
        $x2 = max($request->x1, $request->x2);
        $y1 = min($request->y1, $request->y2);
        $y2 = max($request->y1, $request->y2);

        Localizacion::create([
            'x1' => $x1,
            'y1' => $y1,
            'x2' => $x2,
            'y2' => $y2,
            'tipo' => $request->tipo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Localización guardada correctamente.',
            'redirect' => route('localizaciones.index')
        ]);
    }
}
