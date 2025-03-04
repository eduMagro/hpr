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
        Camion::create($request->all());
        return redirect()->route('camiones.index');
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
