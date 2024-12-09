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

        // Aplicar filtro por código si se pasa como parámetro en la solicitud
        if ($request->has('codigo')) {
            $codigo = $request->input('codigo');
            $query->where('codigo', 'like', '%' . $codigo . '%');
        }
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

    // Método para guardar la ubicación en la base de datos
    public function store(Request $request)
    {
        // Validación de los datos del formulario
        $request->validate([
            'almacen' => 'required|string|max:2',
            'sector' => 'required|string|max:2',
            'ubicacion' => 'required|string|max:2',

        ], [
            // Mensajes personalizados
            'almacen.required' => 'El campo "almacen" es obligatorio.',
            'almacen.string' => 'El campo "almacen" debe ser una cadena de texto.',
            'almacen.max' => 'El campo "almacen" no puede tener más de 2 caracteres.',

            'sector.required' => 'El campo "sector" es obligatorio.',
            'sector.string' => 'El campo "sector" debe ser una cadena de texto.',
            'sector.max' => 'El campo "sector" no puede tener más de 2 caracteres.',

            'ubicacion.required' => 'El campo "ubicacion" es obligatorio.',
            'ubicacion.string' => 'El campo "ubicacion" debe ser una cadena de texto.',
            'ubicacion.max' => 'El campo "ubicacion" no puede tener más de 2 caracteres.',
        ]);

        // Concatenar los campos para formar el código
        $codigo = $request->almacen . $request->sector . $request->ubicacion;

        // Crear la descripción concatenando "Almacén", "Sector" y "Ubicación"
        $descripcion = 'Almacén ' . $request->almacen . ', Sector ' . (int) $request->sector . ', Ubicación ' . (int) $request->ubicacion;


        try {
            // Intentar crear una nueva ubicación en la base de datos
            Ubicacion::create([
                'codigo' => $codigo,  // Guardamos el código generado
                'descripcion' => $descripcion,  // Guardamos la descripción generada
            ]);

            // Redirigir a la página de listado con un mensaje de éxito
            return redirect()->route('ubicaciones.index')->with('success', 'Ubicación creada con éxito.');

        } catch (\Illuminate\Database\QueryException $e) {
            // Si ocurre una excepción (por ejemplo, código duplicado)
            return back()->withErrors(['codigo' => 'Ya existe una ubicación con el mismo código.'])->withInput();
        }
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
        // Buscar la ubicación por ID
        $ubicacion = Ubicacion::findOrFail($id);

        // Verificar si la ubicación tiene productos asociados
        if ($ubicacion->productos()->count() > 0) {
            return redirect()->route('ubicaciones.index')->with('error', 'No se puede eliminar la ubicación porque tiene productos asociados.');
        }

        // Si no tiene productos, proceder a eliminarla
        $ubicacion->delete();

        // Redirigir con éxito
        return redirect()->route('ubicaciones.index')->with('success', 'Ubicación eliminada exitosamente.');
    }

}
