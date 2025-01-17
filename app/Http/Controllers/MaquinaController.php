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
        // Obtener las ubicaciones con sus productos asociados
        $usuarios = User::all();

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
        return view('maquinas.index', compact('registrosMaquina', 'usuarios'));
    }


    //------------------------------------------------------------------------------------ SHOW
    public function show($id)
    {
        $maquina = Maquina::findOrFail($id);
        return view('maquinas.show', compact('maquina'));
    }

    public function create()
    {
        if (auth()->user()->role !== 'administrador') {
            return redirect()->route('maquinas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        return view('maquinas.create');
    }
    // Método para guardar la ubicación en la base de datos
    public function store(Request $request)
    {
        DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
        try {
            // Validación de los datos del formulario
            $request->validate([
                'codigo' => 'required|string|max:6|unique:maquinas,codigo',
                'nombre' => 'required|string|max:40|unique:maquinas,nombre',
                'diametro_min' => 'required|integer',
                'diametro_max' => 'required|integer',
                'peso_min' => 'integer',
                'peso_max' => 'integer',
            ], [
                // Mensajes personalizados
                'codigo.required' => 'El campo "código" es obligatorio.',
                'codigo.string' => 'El campo "código" debe ser una cadena de texto.',
                'codigo.max' => 'El campo "código" no puede tener más de 6 caracteres.',
                'codigo.unique' => 'Ya existe una máquina con el mismo código',

                'nombre.required' => 'El campo "nombre" es obligatorio.',
                'nombre.string' => 'El campo "nombre" debe ser una cadena de texto.',
                'nombre.max' => 'El campo "nombre" no puede tener más de 40 caracteres.',
                'nombre.unique' => 'Ya existe una máquina con el mismo nombre',

                'diametro_min.required' => 'El campo "diámetro mínimo" es obligatorio.',
                'diametro_min.integer' => 'El campo "diámetro mínimo" debe ser un número entero.',

                'diametro_max.required' => 'El campo "diámetro máximo" es obligatorio.',
                'diametro_max.integer' => 'El campo "diámetro máximo" debe ser un número entero.',

                // 'peso_min.required'     => 'El campo "peso mínimo" es obligatorio.',
                'peso_min.integer' => 'El campo "peso mínimo" debe ser un número entero.',

                //'peso_max.required'     => 'El campo "peso máximo" es obligatorio.',
                'peso_max.integer' => 'El campo "peso máximo" debe ser un número entero.',
            ]);


            // Crear la nueva máquina en la base de datos
            Maquina::create([
                'codigo' => $request->codigo,
                'nombre' => $request->nombre,
                'diametro_min' => $request->diametro_min,
                'diametro_max' => $request->diametro_max,
                'peso_min' => $request->peso_min,
                'peso_max' => $request->peso_max,
            ]);

            DB::commit();  // Confirmamos la transacción
            // Redirigir a la página de listado con un mensaje de éxito
            return redirect()->route('maquinas.index')->with('success', 'Máquina creada con éxito.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Mostrar todos los errores de validación
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            // Mostrar errores generales
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }

    public function guardarSesion(Request $request)
    {
        $request->validate([
            'users_id_2' => 'required|exists:users,id',
            'maquina_id' => 'required|exists:maquinas,id'
        ]);

        // Eliminar compañero anterior antes de asignar uno nuevo
        session()->forget('compañero_id');

        // Guardar el nuevo compañero en la sesión
        session(['compañero_id' => $request->users_id_2]);

        return response()->json(['success' => true]);
    }

    public function edit($id)
    {
        if (auth()->user()->role !== 'administrador') {
            return redirect()->route('maquinas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        // Buscar la máquina por su ID
        $maquina = Maquina::findOrFail($id);

        // Retornar la vista con los datos de la máquina
        return view('maquinas.edit', compact('maquina'));
    }

    public function update(Request $request, $id)
    {
        // Validar los datos del formulario
        $validatedData = $request->validate([
            'codigo' => 'required|string|max:6|unique:maquinas,codigo,' . $id,
            'nombre' => 'required|string|max:40',
            'diametro_min' => 'required|integer',
            'diametro_max' => 'required|integer',
            'peso_min' => 'nullable|integer',
            'peso_max' => 'nullable|integer',
        ], [
            'codigo.required' => 'El campo "código" es obligatorio.',
            'codigo.string' => 'El campo "código" debe ser una cadena de texto.',
            'codigo.max' => 'El campo "código" no puede tener más de 6 caracteres.',
            'codigo.unique' => 'El código ya existe, por favor ingrese otro diferente.',

            'nombre.required' => 'El campo "nombre" es obligatorio.',
            'nombre.string' => 'El campo "nombre" debe ser una cadena de texto.',
            'nombre.max' => 'El campo "nombre" no puede tener más de 40 caracteres.',

            'diametro_min.required' => 'El campo "diámetro mínimo" es obligatorio.',
            'diametro_min.integer' => 'El "diámetro mínimo" debe ser un número entero.',

            'diametro_max.required' => 'El campo "diámetro máximo" es obligatorio.',
            'diametro_max.integer' => 'El "diámetro máximo" debe ser un número entero.',

            'peso_min.integer' => 'El "peso mínimo" debe ser un número entero.',
            'peso_max.integer' => 'El "peso máximo" debe ser un número entero.',
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
            return redirect()->back()->with('error', 'Hubo un problema al actualizar la máquina. Intenta nuevamente. Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        if (auth()->user()->role !== 'administrador') {
            return redirect()->route('maquinas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        DB::beginTransaction();
        try {
            // Buscar la maquina a eliminar
            $maquina = Maquina::findOrFail($id);

            // Eliminar la entrada
            $maquina->delete();

            DB::commit();  // Confirmamos la transacción
            return redirect()->route('maquinas.index')->with('success', 'Máquina eliminada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error al eliminar la entrada: ' . $e->getMessage());
        }
    }
}
