<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use Illuminate\Http\Request;

class UbicacionController extends Controller
{

    // private function aplicarFiltros($query, Request $request)
    // {
    //     // Obtener el valor y reemplazar %5F por _
    //     $codigoBarras = str_replace('%5F', '_', $request->input('codigo_barras'));

    //     // Aplicar el filtro con el valor ajustado
    //     $query->where('codigo_barras', 'like', '%' . $codigoBarras . '%');

    //     return $query;
    // }

    // Mostrar todas las ubicaciones
    // Mostrar el índice de ubicaciones
    public function index(Request $request)
    {
        // Obtener las ubicaciones con sus productos asociados
        $ubicaciones = Ubicacion::with('productos');

        $query = Ubicacion::query();
        // $query = $this->aplicarFiltros($query, $request);

        // Ordenar
        $sortBy = $request->input('sort_by', 'created_at');  // Primer criterio de ordenación (nombre)
        $order = $request->input('order', 'desc');        // Orden del primer criterio (asc o desc)

        // Aplicar ordenamiento por múltiples columnas
        $query->orderByRaw("CAST({$sortBy} AS CHAR) {$order}");

        // Paginación
        $perPage = $request->input('per_page', 10);
        $registrosUbicaciones = $query->paginate($perPage)->appends($request->except('page'));

        // Pasar las ubicaciones y productos a la vista
        return view('ubicaciones.index', compact('registrosUbicaciones'));
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
