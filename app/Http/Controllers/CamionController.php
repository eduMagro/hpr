<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use Illuminate\Http\Request;

class CamionController extends Controller
{
    public function index()
    {
        $camiones = Camion::all();
        return view('camiones.index', compact('camiones'));
    }

    public function create()
    {
        return view('camiones.create');
    }

    public function store(Request $request)
    {
        $camion = Camion::create($request->all());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Camión añadido con éxito.',
                'html' => view('empresas-transporte.partials.truck-item', compact('camion'))->render(),
                'empresa_id' => $camion->empresa_id,
                'count' => $camion->empresaTransporte->camiones->count()
            ]);
        }

        return redirect()->route('empresas-transporte.index')->with('success', 'Camión añadido con éxito.');
    }

    public function show(Camion $camion)
    {
        return view('camiones.show', compact('camion'));
    }

    public function edit(Camion $camion)
    {
        return view('camiones.edit', compact('camion'));
    }

    public function update(Request $request, Camion $camion)
    {
        $camion->update($request->all());
        return redirect()->route('camiones.index');
    }

    public function destroy(Camion $camion)
    {
        $camion->delete();
        return redirect()->route('camiones.index');
    }
}
