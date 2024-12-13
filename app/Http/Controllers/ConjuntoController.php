<?php

namespace App\Http\Controllers;

use App\Models\Conjunto;
use App\Models\Planilla;
use Illuminate\Http\Request;

class ConjuntoController extends Controller
{
    /**
     * Muestra una lista de todos los conjuntos.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $conjuntos = Conjunto::with('planilla')->paginate(10);
        return view('conjuntos.index', compact('conjuntos'));
    }

    /**
     * Muestra el formulario para crear un nuevo conjunto.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $planillas = Planilla::all();
        return view('conjuntos.create', compact('planillas'));
    }

    /**
     * Almacena un nuevo conjunto en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'planilla_id' => 'required|exists:planillas,id',
            'codigo' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        Conjunto::create($validated);

        return redirect()->route('conjuntos.index')->with('success', 'Conjunto creado exitosamente.');
    }

    /**
     * Muestra un conjunto específico.
     *
     * @param  \App\Models\Conjunto  $conjunto
     * @return \Illuminate\Http\Response
     */
    public function show(Conjunto $conjunto)
    {
        $conjunto->load('planilla', 'elementos');
        return view('conjuntos.show', compact('conjunto'));
    }

    /**
     * Muestra el formulario para editar un conjunto existente.
     *
     * @param  \App\Models\Conjunto  $conjunto
     * @return \Illuminate\Http\Response
     */
    public function edit(Conjunto $conjunto)
    {
        $planillas = Planilla::all();
        return view('conjuntos.edit', compact('conjunto', 'planillas'));
    }

    /**
     * Actualiza un conjunto existente en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Conjunto  $conjunto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Conjunto $conjunto)
    {
        $validated = $request->validate([
            'planilla_id' => 'required|exists:planillas,id',
            'codigo' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $conjunto->update($validated);

        return redirect()->route('conjuntos.index')->with('success', 'Conjunto actualizado exitosamente.');
    }

    /**
     * Elimina un conjunto existente de la base de datos.
     *
     * @param  \App\Models\Conjunto  $conjunto
     * @return \Illuminate\Http\Response
     */
    public function destroy(Conjunto $conjunto)
    {
        $conjunto->delete();
        return redirect()->route('conjuntos.index')->with('success', 'Conjunto eliminado exitosamente.');
    }
}

