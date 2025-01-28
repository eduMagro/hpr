<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{

    //------------------------------------------------------------------------------------ FILTROS
   public function aplicarFiltros(Request $request)
   {
       $query = Producto::query();
   
       // Aplicar filtros si están presentes en la solicitud
       if ($request->filled('id')) {
           $query->where('id', $request->id);
       }
       if ($request->filled('fabricante')) {
           $query->where('fabricante', $request->fabricante);
       }
       if ($request->filled('tipo')) {
           $query->where('tipo', $request->tipo);
       }
       if ($request->filled('diametro')) {
           $query->where('diametro', $request->diametro);
       }
       if ($request->filled('longitud')) {
           $query->where('longitud', $request->longitud);
       }
       if ($request->filled('n_colada')) {
           $query->where('n_colada', $request->n_colada);
       }
       if ($request->filled('n_paquete')) {
           $query->where('n_paquete', $request->n_paquete);
       }
       if ($request->filled('estado')) {
           $query->where('estado', 'LIKE', '%' . $request->estado . '%');
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
        if (auth()->user()->categoria !== 'administrador') {
            return redirect()->route('productos.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        return view('productos.edit', compact('producto'));
    }

    //------------------------------------------------------------------------------------ UPDATE
    public function update(Request $request, Producto $producto)
    {
        // Mensajes personalizados de validación
        $messages = [
            'fabricante.in' => 'El fabricante debe ser MEGASA, Getafe, Siderúrgica Sevillana o NERVADUCTIL.',

            'nombre.string'          => 'El nombre debe ser una cadena de texto.',
            'nombre.max'             => 'El nombre no puede tener más de 255 caracteres.',

            'tipo.in' => 'El tipo debe ser "encarretado" o "barras".',

            'diametro.in' => 'El diámetro debe ser 8, 10, 12, 16, 20, 25 o 32.',

            'longitud.in' => 'La longitud debe ser 6, 12, 14, 15 o 16.',

            'n_colada.string'        => 'El número de colada debe ser una cadena de texto.',
            'n_colada.max'           => 'El número de colada no puede tener más de 255 caracteres.',

            'n_paquete.string'       => 'El número de paquete debe ser una cadena de texto.',
            'n_paquete.max'          => 'El número de paquete no puede tener más de 255 caracteres.',

            'peso_inicial.required'  => 'El peso inicial es obligatorio.',
            'peso_inicial.numeric'   => 'El peso inicial debe ser un número decimal.',

            'peso_stock.required'    => 'El peso en stock es obligatorio.',
            'peso_stock.numeric'     => 'El peso en stock debe ser un número decimal.',

            'ubicacion_id.integer'   => 'La ubicación debe ser un identificador válido.',
            'ubicacion_id.exists'    => 'La ubicación seleccionada no es válida.',

            'maquina_id.integer'     => 'La máquina debe ser un identificador válido.',
            'maquina_id.exists'      => 'La máquina seleccionada no es válida.',

            'estado.string'          => 'El estado debe ser una cadena de texto.',
            'estado.max'             => 'El estado no puede tener más de 50 caracteres.',

            'otros.string'           => 'El campo "Otros" debe ser una cadena de texto.',
        ];

        // Validación de datos con reglas ajustadas a la base de datos
        $validatedData = $request->validate([
            'fabricante' => 'required|in:MEGASA,Getafe,Siderúrgica Sevillana,NERVADUCTIL',
            'nombre'         => 'nullable|string|max:255',
            'tipo' => 'required|in:encarretado,barras',
            'diametro' => 'required|in:8,10,12,16,20,25,32',
            'longitud' => 'nullable|in:6,12,14,15,16',
            'n_colada'       => 'required|string|max:255',
            'n_paquete'      => 'required|string|max:255',
            'peso_inicial'   => 'required|numeric|between:0,9999999.99',
            'peso_stock'     => 'required|numeric|between:0,9999999.99',
            'ubicacion_id'   => 'nullable|integer|exists:ubicaciones,id',
            'maquina_id'     => 'nullable|integer|exists:maquinas,id',
            'estado'         => 'nullable|string|max:50',
            'otros'          => 'nullable|string',
        ], $messages);


        // Actualizar el producto con los datos validados
        $producto->update($validatedData);

        // Redireccionar con mensaje de éxito
        return redirect()->route('productos.index')->with('success', 'Producto actualizado con éxito.');
    }


    //------------------------------------------------------------------------------------ DESTROY
    public function destroy(Producto $producto)
    {
        if (auth()->user()->categoria !== 'administrador') {
            return redirect()->route('productos.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        $producto->delete();

        return redirect()->route('productos.index')->with('success', 'Producto eliminado exitosamente.');
    }
}
