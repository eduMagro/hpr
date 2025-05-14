<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use App\Models\Localizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;

class UbicacionController extends Controller
{
    //------------------------------------------------------------------------------------ INDEX()
    public function index(Request $request)
    {
        try {
            // Obtener ubicaciones con productos y paquetes, ordenadas por sector y ubicación
            $ubicaciones = Ubicacion::with(['productos', 'paquetes'])
                ->orderBy('sector', 'desc')
                ->orderBy('ubicacion', 'asc')
                ->get();

            // Agrupar por sector
            $ubicacionesPorSector = $ubicaciones->groupBy('sector');

            // // Inicializar arrays para los totales de productos
            // $pesoEncarretadoPorDiametro = [];
            // $pesoBarrasPorLongitud = [];

            // // Calcular pesos totales por tipo de producto (materia prima)
            // foreach ($ubicaciones as $ubicacion) {
            //     foreach ($ubicacion->productos as $producto) {
            //         if ($producto->tipo === 'encarretado') {
            //             if (!isset($pesoEncarretadoPorDiametro[$producto->diametro])) {
            //                 $pesoEncarretadoPorDiametro[$producto->diametro] = 0;
            //             }
            //             $pesoEncarretadoPorDiametro[$producto->diametro] += $producto->peso_inicial;
            //         } elseif ($producto->tipo === 'barras') {
            //             if (!isset($pesoBarrasPorLongitud[$producto->longitud])) {
            //                 $pesoBarrasPorLongitud[$producto->longitud] = 0;
            //             }
            //             $pesoBarrasPorLongitud[$producto->longitud] += $producto->peso_inicial;
            //         }
            //     }
            // }

            // // Ordenar los arrays por clave (menor a mayor)
            // ksort($pesoEncarretadoPorDiametro);
            // ksort($pesoBarrasPorLongitud);

            // Pasar todos los datos necesarios a la vista
            return view('ubicaciones.index', [
                'ubicacionesPorSector' => $ubicacionesPorSector
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }

    //------------------------------------------------------------------------------------ CREATE()
    public function create()
    {
        return view('ubicaciones.create');
    }
    //------------------------------------------------------------------------------------ STORE()
    public function store(Request $request)
    {
        $request->validate([
            'ubicaciones' => 'required|array',
            'ubicaciones.*.x' => 'required|integer|min:1',
            'ubicaciones.*.y' => 'required|integer|min:1',
            'ubicaciones.*.tipo' => 'required|in:material,maquina,transitable',
        ]);

        foreach ($request->ubicaciones as $data) {
            Localizacion::updateOrCreate(
                ['x' => $data['x'], 'y' => $data['y']],
                ['tipo' => $data['tipo']]
            );
        }

        return response()->json(['message' => 'Ubicaciones guardadas correctamente']);
    }
    //------------------------------------------------------------------------------------ SHOW()
    public function show($id)
    {
        // Encuentra la ubicación por ID con relaciones 'productos' y 'paquetes'
        $ubicacion = Ubicacion::with(['productos', 'paquetes'])->findOrFail($id);

        // Retorna la vista con la ubicación y sus relaciones
        return view('ubicaciones.show', compact('ubicacion'));
    }


    // Mostrar el formulario para editar una ubicación existente
    public function edit($id)
    {
        $ubicacion = Ubicacion::findOrFail($id);
        return view('ubicaciones.edit', compact('ubicacion'));
    }

    //------------------------------------------------------------------------------------ UPDATE()
    public function update(Request $request, $id)
    {
        try {

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
            return redirect()->route('ubicaciones.index')->with('success', 'Ubicación actualizada con éxito.');
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

    //------------------------------------------------------------------------------------ DESTROY()
    public function destroy($id)
    {
        try {
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
}
