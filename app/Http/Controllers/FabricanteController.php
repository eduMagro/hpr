<?php

namespace App\Http\Controllers;

use App\Models\Fabricante;
use Illuminate\Http\Request;

class FabricanteController extends Controller
{
    public function index()
    {
        $fabricantes = Fabricante::all();
        return view('fabricantes.index', compact('fabricantes'));
    }

    public function create()
    {
        return view('fabricantes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        Fabricante::create($request->all());

        return redirect()->route('fabricantes.index')->with('success', 'Fabricante creado correctamente.');
    }

    public function show(Fabricante $fabricante)
    {
        return view('fabricantes.show', compact('fabricante'));
    }

    public function edit(Fabricante $fabricante)
    {
        return view('fabricantes.edit', compact('fabricante'));
    }

    public function update(Request $request, Fabricante $fabricante)
    {
        $fabricante->update($request->all());
        return redirect()->route('fabricantes.index')->with('success', 'Fabricante actualizado.');
    }

    public function destroy(Fabricante $fabricante)
    {
        $fabricante->delete();
        return redirect()->route('fabricantes.index')->with('success', 'Fabricante eliminado.');
    }
}
