<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use Illuminate\Http\Request;

class UbicacionController extends Controller
{
    // Mostrar todas las ubicaciones
    public function index()
    {
        $ubicaciones = Ubicacion::all();  // Obtener todas las ubicaciones
        return view('ubicaciones.index', compact('ubicaciones'));
    }

    // Mostrar el formulario para crear una nueva ubicación
    public function create()
    {
        return view('ubicaciones.create');
    }

    // Guardar una nueva ubicación
    public function store(Request $request)
    {
        // Validar los datos
        $request->validate([
            'codigo' => 'required|string|max:255|unique:ubicaciones',
            'descripcion' => 'nullable|string',
        ]);

        // Crear la ubicación
        Ubicacion::create([
            'codigo' => $request->codigo,
            'descripcion' => $request->descripcion,
        ]);

        // Redirigir a la lista de ubicaciones con un mensaje de éxito
        return redirect()->route('ubicaciones.index')->with('success', 'Ubicación creada exitosamente.');
    }

    // Mostrar el formulario para editar una ubicación existente
    public function edit($id)
    {
        $ubicacion = Ubicacion::findOrFail($id);
        return view('ubicaciones.edit', compact('ubicacion'));
    }

    // Actualizar la ubicación
    public function update(Request $request, $id)
    {
        $ubicacion = Ubicacion::findOrFail($id);

        // Validar los datos
        $request->validate([
            'codigo' => 'required|string|max:255|unique:ubicaciones,codigo,' . $ubicacion->id,
            'descripcion' => 'nullable|string',
        ]);

        // Actualizar la ubicación
        $ubicacion->update([
            'codigo' => $request->codigo,
            'descripcion' => $request->descripcion,
        ]);

        // Redirigir a la lista de ubicaciones con un mensaje de éxito
        return redirect()->route('ubicaciones.index')->with('success', 'Ubicación actualizada exitosamente.');
    }

    // Eliminar una ubicación
    public function destroy($id)
    {
        $ubicacion = Ubicacion::findOrFail($id);
        $ubicacion->delete();

        return redirect()->route('ubicaciones.index')->with('success', 'Ubicación eliminada exitosamente.');
    }
}
