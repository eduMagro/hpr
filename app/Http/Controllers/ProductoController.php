<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{

    //------------------------------------------------------------------------------------ FILTROS
    private function aplicarFiltros($query, Request $request)
    {
        $buscar = $request->input('id');
        if (!empty($buscar)) {
            $query->where('id', $buscar);
        }
        return $query;
    }

    //------------------------------------------------------------------------------------ INDEX
    public function index(Request $request)
    {
        // Inicializa la consulta de productos
        $query = Producto::query();

        // Aplica los filtros
        $query = $this->aplicarFiltros($query, $request);

        // Establecer el criterio de ordenación basado en los parámetros de la solicitud
        // Si no se pasa un criterio de ordenación, se ordenará por la fecha de creación ('created_at') por defecto
        $sortBy = $request->input('sort_by', 'created_at');  // Obtener el valor del parámetro 'sort_by' o 'created_at' por defecto
        $order = $request->input('order', 'desc');        // Obtener el valor del parámetro 'order' o 'desc' por defecto (descendente)

        // Aplicar el ordenamiento dinámico a la consulta según el criterio de ordenación y el orden (asc/desc)
        // 'CAST({$sortBy} AS CHAR)' convierte el valor de la columna para asegurar que se ordena como texto
        $query->orderByRaw("CAST({$sortBy} AS CHAR) {$order}");

        // Obtener el valor de paginación, si no se pasa un valor se utilizará 10 como valor predeterminado
        $perPage = $request->input('per_page', 10);

        // Ejecutar la consulta con paginación
        // 'paginate($perPage)' divide los resultados en páginas según el valor de 'perPage'
        // 'appends($request->except('page'))' mantiene los parámetros de búsqueda en la URL durante la paginación
        $registrosProductos = $query->paginate($perPage)->appends($request->except('page'));

        // Pasar los productos paginados a la vista para mostrar los datos
        return view('productos.index', compact(
            'registrosProductos'
        ));
    }
    //------------------------------------------------------------------------------------ SHOW
    public function show($id)
    {
        $detalles_producto = Producto::findOrFail($id);
        return view('productos.show', compact('detalles_producto'));
    }

  //------------------------------------------------------------------------------------ CREATE
    public function create()
    {
        return view('productos.create');
    }

   //------------------------------------------------------------------------------------ STORE
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
        ]);

        Producto::create($request->all());

        return redirect()->route('productos.index')->with('success', 'Producto creado exitosamente.');
    }

   //------------------------------------------------------------------------------------ EDIT
    public function edit(Producto $producto)
    {
        return view('productos.edit', compact('producto'));
    }

  //------------------------------------------------------------------------------------ UPDATE
    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
        ]);

        $producto->update($request->all());

        return redirect()->route('productos.index')->with('success', 'Producto actualizado exitosamente.');
    }

    //------------------------------------------------------------------------------------ DESTROY
    public function destroy(Producto $producto)
    {
        $producto->delete();

        return redirect()->route('productos.index')->with('success', 'Producto eliminado exitosamente.');
    }
}
