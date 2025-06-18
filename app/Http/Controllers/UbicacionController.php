<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

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
        DB::beginTransaction(); // Iniciar la transacción
        try {
            // Validación de los datos del formulario
            $request->validate([
                'almacen' => 'required|string|max:2',
                'sector' => 'required|string|max:2',
                'ubicacion' => 'required|string|max:2',
                'descripcion' => 'nullable|string|max:255',
            ], [
                // Mensajes personalizados
                'almacen.required' => 'El campo "almacén" es obligatorio.',
                'almacen.string' => 'El campo "almacén" debe ser una cadena de texto.',
                'almacen.max' => 'El campo "almacén" no puede tener más de 2 caracteres.',

                'sector.required' => 'El campo "sector" es obligatorio.',
                'sector.string' => 'El campo "sector" debe ser una cadena de texto.',
                'sector.max' => 'El campo "sector" no puede tener más de 2 caracteres.',

                'ubicacion.required' => 'El campo "ubicación" es obligatorio.',
                'ubicacion.string' => 'El campo "ubicación" debe ser una cadena de texto.',
                'ubicacion.max' => 'El campo "ubicación" no puede tener más de 2 caracteres.',

                'descripcion.string' => 'El campo "descripción" debe ser una cadena de texto.',
                'descripcion.max' => 'El campo "descripción" no puede tener más de 255 caracteres.',
            ]);

            // Concatenar los campos para formar el código único
            $codigo = $request->almacen . $request->sector . $request->ubicacion;

            // Crear el nombre concatenando "Almacén", "Sector" y "Ubicación"
            $nombre = 'Almacén ' . $request->almacen . ', Sector ' . (int) $request->sector . ', Ubicación ' . (int) $request->ubicacion;

            // Si hay una descripción, añadirla al nombre
            if (!empty($request->descripcion)) {
                $nombre .= ', ' . $request->descripcion;
            }

            // Verificar si ya existe una ubicación con ese código
            if (Ubicacion::where('codigo', $codigo)->exists()) {
                DB::rollBack();  // Revertir la transacción si ya existe
                return back()->withErrors(['error' => 'Esta ubicación ya existe.'])->withInput();
            }

            // Intentar crear una nueva ubicación en la base de datos
            Ubicacion::create([
                'codigo' => $codigo,        // Guardamos el código generado
                'nombre' => $nombre,        // Guardamos el nombre correctamente
                'almacen' => $request->almacen,
                'sector' => $request->sector,
                'ubicacion' => $request->ubicacion,
                'descripcion' => $request->descripcion, // Guardamos la descripción original
            ]);

            DB::commit();  // Confirmar la transacción
            return redirect()->route('ubicaciones.index')->with('success', 'Ubicación creada con éxito.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear ubicación: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Hubo un problema al guardar la ubicación.'])->withInput();
        }
    }
    //------------------------------------------------------------------------------------ SHOW()
    public function show($id)
    {
        $ubicacion = Ubicacion::with([
            'productos.fabricante',     // 👈 añade el fabricante
            'productos.productoBase',   // 👈 ya tenías esta
            'paquetes'                  // 👈 también los paquetes
        ])->findOrFail($id);

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
