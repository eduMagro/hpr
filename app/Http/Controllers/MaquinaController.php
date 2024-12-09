<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
use Illuminate\Support\Facades\DB;

class MaquinaController extends Controller
{
    public function index(Request $request)
    {
        // Obtener las ubicaciones con sus productos asociados
        $maquinas = Maquina::with('productos');

        $query = Maquina::query();
        // $query = $this->aplicarFiltros($query, $request);

        // Aplicar filtro por código si se pasa como parámetro en la solicitud
        if ($request->has('nombre')) {
            $nombre = $request->input('nombre');
            $query->where('nombre', 'like', '%' . $nombre . '%');
        }
        // Ordenar
        $sortBy = $request->input('sort_by', 'created_at');  // Primer criterio de ordenación (nombre)
        $order = $request->input('order', 'desc');        // Orden del primer criterio (asc o desc)

        // Aplicar ordenamiento por múltiples columnas
        $query->orderByRaw("CAST({$sortBy} AS CHAR) {$order}");

        // Paginación
        $perPage = $request->input('per_page', 10);
        $registrosMaquina = $query->paginate($perPage)->appends($request->except('page'));

        // Pasar las ubicaciones y productos a la vista
        return view('maquinas.index', compact('registrosMaquina'));
    }
    public function create()
    {
        return view('maquinas.create');
    }
    // Método para guardar la ubicación en la base de datos
    public function store(Request $request)
    {
        DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
        // Validación de los datos del formulario
        $request->validate([
            'codigo' => 'required|string|max:6',
            'nombre' => 'required|string|max:40',

        ], [
            // Mensajes personalizados
            'codigo.required' => 'El campo "código" es obligatorio.',
            'codigo.string' => 'El campo "código" debe ser una cadena de texto.',
            'codigo.max' => 'El campo "código" no puede tener más de 6 caracteres.',

            'nombre.required' => 'El campo "nombre" es obligatorio.',
            'nombre.string' => 'El campo "nombre" debe ser una cadena de texto.',
            'nombre.max' => 'El campo "nombre" no puede tener más de 40 caracteres.',
        ]);

        try {
            // Intentar crear una nueva ubicación en la base de datos
            Maquina::create([
                'codigo' => $request->codigo,  // Guardamos el código generado
                'nombre' => $request->nombre,  // Guardamos la descripción generada
            ]);
            DB::commit();  // Confirmamos la transacción
            // Redirigir a la página de listado con un mensaje de éxito
            return redirect()->route('maquinas.index')->with('success', 'Máquina creada con éxito.');

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            // Si ocurre una excepción (por ejemplo, código duplicado)
            return back()->withErrors(['codigo' => 'Ya existe una máquina con el mismo código.'])->withInput();
        }
    }

    public function edit($id)
    {
        // Buscar la máquina por su ID
        $maquina = Maquina::findOrFail($id);

        // Retornar la vista con los datos de la máquina
        return view('maquinas.edit', compact('maquina'));
    }

    public function update(Request $request, $id)
    {
        // Validar los datos del formulario
        $validatedData = $request->validate([
            'codigo' => 'required|string|max:255|unique:maquinas,codigo,' . $id,
            'nombre' => 'required|string|max:255',
        ]);
    
        // Iniciar la transacción
        DB::beginTransaction();
    
        try {
            // Buscar la máquina por su ID
            $maquina = Maquina::findOrFail($id);
    
            // Actualizar los datos de la máquina
            $maquina->update($validatedData);
    
            // Confirmar la transacción
            DB::commit();
    
            // Redirigir con un mensaje de éxito
            return redirect()->route('maquinas.index')->with('success', 'La máquina se actualizó correctamente.');
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();
    
            // Redirigir con un mensaje de error
            return redirect()->back()->with('error', 'Hubo un problema al actualizar la máquina. Intenta nuevamente.');
        }
    }
}
