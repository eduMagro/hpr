<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Ubicacion;
use App\Models\User;
use App\Models\EntradaProducto;
use App\Models\Producto;  // Asegúrate de importar Producto
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class EntradaController extends Controller
{
    //------------------------------------------------------------------------------------ FILTROS
    private function aplicarFiltros($query, Request $request)
    {
        //$buscar = $request->input('albaran');
        //if (!empty($buscar)) {
        //   $query->where('albaran', $buscar);
        //}
        //return $query;
        // Filtro por 'id' si está presente
        if ($request->has('albaran') && $request->albaran) {
            $albaran = $request->input('albaran');
            $query->where('albaran', '=', $albaran);  // Filtro exacto por ID
        }

        // Filtro por 'fecha' si está presente y busca en la columna 'created_at' usando LIKE
        if ($request->has('fecha') && $request->fecha) {
            $fecha = $request->input('fecha');  // Obtener el valor de la fecha proporcionada

            // Buscar en la columna 'created_at' utilizando LIKE para buscar por año, mes o día
            $query->whereRaw('DATE(created_at) LIKE ?', ['%' . $fecha . '%']);
        }

        return $query;
    }

    // Mostrar todas las entradas
    public function index(Request $request)
    {
        try {
            // Inicializa la consulta de productos con sus relaciones necesarias
            $query = Entrada::with(['ubicacion', 'user', 'productos']);

            // Aplica los filtros mediante un método separado
            $query = $this->aplicarFiltros($query, $request);

            // Obtener las entradas paginadas, ordenadas por fecha de creación
            $entradas = $query->orderBy('created_at', 'desc')->paginate(10);

            // Devolver la vista con las entradas
            return view('entradas.index', compact('entradas'));
        } catch (ValidationException $e) {
            // Manejo de excepciones de validación
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {
            // Manejo de excepciones generales
            return redirect()->back()
                ->with('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }

    // Mostrar el formulario de creación
    public function create()
    {
        $ubicaciones = Ubicacion::all();
        $usuarios = User::all();

        return view('entradas.create', compact('ubicaciones', 'usuarios'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'fabricante' => 'required|in:MEGASA,GETAFE,NERVADUCTIL,SIDERURGICA SEVILLANA',
                'albaran' => 'required|string|min:1|max:30',
                'tipo' => 'required|in:ENCARRETADO,BARRA',
                'diametro' => 'required|numeric|in:5,8,10,12,16,20,25,32',
                'longitud' => 'nullable|numeric|in:6,12,14,15,16',
                'n_colada' => 'required|string|max:50',
                'n_paquete' => 'required|string|max:50',
                'peso' => 'required|numeric|min:1',
                'ubicacion' => 'nullable|string|max:100',
                'otros' => 'nullable|string|max:255',
            ], [
                'fabricante.required' => 'El fabricante es obligatorio.',
                'fabricante.in' => 'El fabricante seleccionado no es válido.',
                'albaran.required' => 'El número de albarán es obligatorio.',
                'tipo.required' => 'El tipo de paquete es obligatorio.',
                'diametro.required' => 'El diámetro es obligatorio.',
                'n_colada.required' => 'El número de colada es obligatorio.',
                'n_paquete.required' => 'El número de paquete es obligatorio.',
                'peso.required' => 'El peso es obligatorio.',
            ]);

            // Crear la entrada
            $entrada = Entrada::create([
                'albaran' => $request->albaran,
                'users_id' => auth()->id(),
                'otros' => $request->otros ?? null,
            ]);

            // Crear producto
            $producto = Producto::create([
                'fabricante' => $request->fabricante,
                'nombre' => 'CORRUGADO', // Puedes ajustar esto según sea necesario
                'tipo' => $request->tipo,
                'diametro' => $request->diametro,
                'longitud' => $request->longitud ?? NULL,
                'n_colada' => $request->n_colada,
                'n_paquete' => $request->n_paquete,
                'peso_inicial' => $request->peso,
                'peso_stock' => $request->peso,
                'ubicacion_id' => $request->ubicacion, // Debes relacionar esto con una ubicación existente
                'maquina_id' => null, // Puedes cambiarlo según sea necesario
                'estado' => 'ALMACENADO',
                'otros' => $request->otros ?? null,
            ]);

            // Crear la relación en la tabla intermedia
            EntradaProducto::create([
                'entrada_id' => $entrada->id,
                'producto_id' => $producto->id,
                'ubicacion_id' => $request->ubicacion,
                'users_id' => auth()->id(),
            ]);

            DB::commit();
            return redirect()->route('entradas.index')->with('success', 'Entrada registrada correctamente.');
        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Hubo un problema al registrar la entrada: ' . $e->getMessage())->withInput();
        }
    }


    public function edit($id)
    {
        $entrada = Entrada::findOrFail($id);  // Encuentra la entrada por su ID
        $ubicaciones = Ubicacion::all();  // Cargar todas las ubicaciones
        return view('entradas.edit', compact('entrada', 'ubicaciones'));
    }

    public function update(Request $request, Entrada $entrada)
    {
        DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
        try {
            $request->validate([
                'fabricante' => 'required|string|max:255',
                'albaran' => 'required|string|min:5|max:15|alpha_num',
                'peso_total' => 'required|numeric|min:1',
            ]);

            $entrada->update([
                'fabricante' => $request->fabricante,
                'albaran' => $request->albaran,
                'peso_total' => $request->peso_total,
            ]);
            DB::commit();
            return redirect()->route('entradas.index')->with('success', 'Entrada de material actualizada correctamente.');
        } catch (ValidationException $e) {
            // Mostrar todos los errores de validación
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            // Mostrar errores generales
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }



    // Eliminar una entrada y sus productos asociados
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // Buscar la entrada a eliminar
            $entrada = Entrada::findOrFail($id);

            // Eliminar los productos asociados a la entrada
            $entrada->productos()->delete();

            // Eliminar la entrada
            $entrada->delete();
            DB::commit();  // Confirmamos la transacción
            return redirect()->route('entradas.index')->with('success', 'Entrada eliminada correctamente.');
        } catch (Exception $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error al eliminar la entrada: ' . $e->getMessage());
        }
    }
}
