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
<<<<<<< HEAD
        $buscar = $request->input('albaran');
        if (!empty($buscar)) {
            $query->where('albaran', $buscar);
        }
        return $query;
=======
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
>>>>>>> 6fea693 (primercommit)
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
                'albaran' => 'nullable|string|max:255',
                'fabricante' => 'nullable|array',
                'fabricante.*' => 'nullable|string|max:255',
                'producto_nombre' => 'required|array|min:1',
                'producto_nombre.*' => 'required|string|max:255',
                'producto_codigo' => 'required|array|min:1',
                'producto_codigo.*' => 'required|string|max:255',
                'producto_peso' => 'nullable|array',
                'producto_peso.*' => 'nullable|numeric|min:0',
                'producto_otros' => 'nullable|array',
                'producto_otros.*' => 'nullable|string|max:255',
                'ubicacion_id' => 'required|array|min:1',
                'ubicacion_id.*' => 'required|exists:ubicaciones,id',
            ]);

            // Encuentra la entrada que se desea actualizar
            $entrada = Entrada::findOrFail($id);

            // Actualizar la entrada (albarán)
            $entrada->albaran = $request->albaran ?? $entrada->albaran; // Usar albarán actual si no se proporciona uno nuevo
            $entrada->otros = $request->producto_otros[0] ?? $entrada->otros; // Usar "otros" del primer producto si no se proporciona uno nuevo
            $entrada->save();

            // Actualizar los productos
            foreach ($request->producto_nombre as $index => $nombre) {
                // Dividir el código de barras en segmentos
                $segmentos = explode('_', $request->producto_codigo[$index]);

                if (count($segmentos) !== 7) {
                    return redirect()->back()->with('error', 'El código de barras del fabricante no tiene el formato esperado.');
                }

                // Extraer datos del código de barras
                $n_colada = $segmentos[1] ?? null;
                $n_paquete = $segmentos[2] ?? null;
                $diametro = isset($segmentos[4]) ? str_replace('D', '', $segmentos[4]) : null;
                $longitud = isset($segmentos[5]) ? str_replace('L', '', $segmentos[5]) : null;

                // Generar el código de barras final concatenando los datos introducidos
                $qr = implode('_', [
                    "N" . $nombre,
                    "D" . ($diametro / 100 ?? 'NULL'),
                    "L" . ($longitud / 100 ?? 'NULL'),
                    "P" . $request->producto_peso[$index],
                    now()->timestamp,
                ]);

                // Buscar el producto a actualizar
                $producto = Producto::find($request->producto_id[$index]);
                if ($producto) {
                    // Actualizar los datos del producto
                    $producto->nombre = $nombre;
                    $producto->qr = $qr;
                    $producto->diametro = $diametro;
                    $producto->longitud = $longitud;
                    $producto->fabricante = $request->fabricante[$index] ?? $producto->fabricante;
                    $producto->n_colada = $n_colada;
                    $producto->n_paquete = $n_paquete;
                    $producto->ubicacion_id = $request->ubicacion_id[$index];
                    $producto->peso = $request->producto_peso[$index] ?? $producto->peso;
                    $producto->otros = $request->producto_otros[$index] ?? $producto->otros;
                    $producto->save();
                } else {
                    // Si no se encuentra el producto, crearlo como nuevo
                    $producto = Producto::create([
                        'nombre' => $nombre,
                        'qr' => $qr,
                        'diametro' => $diametro,
                        'longitud' => $longitud,
                        'fabricante' => $request->fabricante[$index] ?? null,
                        'n_colada' => $n_colada,
                        'n_paquete' => $n_paquete,
                        'ubicacion_id' => $request->ubicacion_id[$index],
                        'peso' => $request->producto_peso[$index] ?? 0,
                        'otros' => $request->producto_otros[$index] ?? null,
                    ]);
                }

                // Relacionar el producto con la entrada si no está relacionado
                if (!$entrada->productos->contains($producto->id)) {
                    $entrada->productos()->attach($producto->id, ['qr' => $qr]);
                }
            }
            DB::commit();  // Confirmamos la transacción
            return redirect()->route('entradas.index')->with('success', 'Entrada actualizada correctamente con ' . count($request->producto_nombre) . ' productos.');
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
