<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Ubicacion;
use App\Models\User;
use App\Models\Producto;  // Asegúrate de importar Producto
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;
use App\Services\Fabricantes\FabricanteServiceFactory;
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

        // Definir los nombres de los productos en el controlador
        $nombre_productos = [
            'Corrugado',
            'Tipo 2',
            'Tipo 3',
        ];

        return view('entradas.create', compact('ubicaciones', 'usuarios', 'nombre_productos'));
    }

    public function store(Request $request)
    {
        try {
            // Obtener el servicio adecuado para el fabricante
            $fabricanteService = FabricanteServiceFactory::getService($request->fabricante);

            // Delegar la lógica específica al servicio
            $fabricanteService->procesar($request);

            return redirect()->route('entradas.index')->with('success', 'Entrada registrada correctamente.');
        } catch (ValidationException $e) {
            // Manejar errores de validación
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            // Manejar errores generales
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $entrada = Entrada::findOrFail($id);  // Encuentra la entrada por su ID
        $ubicaciones = Ubicacion::all();  // Cargar todas las ubicaciones
        return view('entradas.edit', compact('entrada', 'ubicaciones'));
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
        try {
            // Validar los datos enviados
            $request->validate([
                'fabricante' => 'nullable|string|max:255',
                'nombre' => 'required|string|max:255',
                'tipo' => 'nullable|string|max:255',
                'diametro' => 'nullable|numeric|min:0',
                'longitud' => 'nullable|numeric|min:0',
                'n_colada' => 'nullable|string|max:255',
                'n_paquete' => 'nullable|string|max:255',
                'peso_inicial' => 'required|numeric|min:0',
                'peso_stock' => 'required|numeric|min:0',
                'ubicacion_id' => 'nullable|exists:ubicaciones,id',
                'maquina_id' => 'nullable|exists:maquinas,id',
                'estado' => 'nullable|string|max:255',
                'otros' => 'nullable|string|max:255',
            ]);

            // Buscar el producto a actualizar
            $producto = Producto::findOrFail($id);

            // Actualizar los datos del producto
            $producto->update($request->all());

            DB::commit();  // Confirmamos la transacción
            return redirect()->route('productos.index')->with('success', 'Producto actualizado correctamente.');
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
