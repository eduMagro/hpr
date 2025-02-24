<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Obra;

class ObraController extends Controller
{
    public function index()
    {
        $obras = Obra::paginate(10);
        return view('obras.index', compact('obras'));
    }

    public function create()
    {
        return view('obras.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'obra' => 'required|string|max:255',
            'cod_obra' => 'required|string|max:50|unique:obras,cod_obra',
            'cliente' => 'required|string|max:255',
            'cod_cliente' => 'nullable|string|max:50',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'distancia' => 'nullable|integer',
        ]);

        Obra::create($request->all());
        return redirect()->route('obras.index')->with('success', 'Obra creada correctamente.');
    }

    public function edit(Obra $obra)
    {
        return view('obras.edit', compact('obra'));
    }

    public function update(Request $request, Obra $obra)
    {
        $request->validate([
            'obra' => 'required|string|max:255',
            'cod_obra' => 'required|string|max:50|unique:obras,cod_obra,' . $obra->id,
            'cliente' => 'required|string|max:255',
            'cod_cliente' => 'nullable|string|max:50',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'distancia' => 'nullable|integer',
        ]);

        $obra->update($request->all());
        return redirect()->route('obras.index')->with('success', 'Obra actualizada correctamente.');
    }

    public function destroy(Obra $obra)
    {
        $obra->delete();
        return redirect()->route('obras.index')->with('success', 'Obra eliminada correctamente.');
    }
}
