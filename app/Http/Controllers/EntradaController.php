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


class EntradaController extends Controller
{

    // Mostrar todas las entradas
    public function index()
    {
        try {
            // Obtener las entradas paginadas con sus relaciones (ubicación, usuario y producto)
            $entradas = Entrada::with(['ubicacion', 'user', 'productos'])  // Cargamos las relaciones necesarias
                ->orderBy('created_at', 'desc')  // Opcional: ordenar por fecha de creación
                ->paginate(10);  // 10 entradas por página

            // Devolver la vista con las entradas
            return view('entradas.index', compact('entradas'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Mostrar todos los errores de validación
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            // Mostrar errores generales
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
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
        DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
        try {
            // Validar los datos enviados
            $validatedData = $request->validate([
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
            ], [
                // Mensajes personalizados para cada campo
                'producto_nombre.required' => 'El campo "nombre del producto" es obligatorio.',
                'producto_nombre.array' => 'El campo "nombre del producto" debe ser un arreglo.',
                'producto_nombre.min' => 'Debes ingresar al menos un nombre de producto.',
                'producto_nombre.*.string' => 'Cada nombre de producto debe ser una cadena de texto.',
                'producto_nombre.*.max' => 'Cada nombre de producto no puede tener más de 255 caracteres.',

                'producto_codigo.required' => 'El campo "código del producto" es obligatorio.',
                'producto_codigo.array' => 'El campo "código del producto" debe ser un arreglo.',
                'producto_codigo.min' => 'Debes ingresar al menos un código de producto.',
                'producto_codigo.*.string' => 'El código de producto debe ser una cadena de texto.',
                'producto_codigo.*.max' => 'Cada código de producto no puede tener más de 255 caracteres.',

                'producto_peso.array' => 'El campo "peso del producto" debe ser un arreglo.',
                'producto_peso.*.numeric' => 'El peso del producto debe ser un valor numérico.',
                'producto_peso.*.min' => 'El peso del producto no puede ser negativo.',

                'producto_otros.array' => 'El campo "otros detalles" debe ser un arreglo.',
                'producto_otros.*.string' => 'Cada detalle debe ser una cadena de texto.',
                'producto_otros.*.max' => 'Cada detalle no puede tener más de 255 caracteres.',

                'ubicacion_id.required' => 'El campo "ubicación" es obligatorio.',
                'ubicacion_id.array' => 'El campo "ubicación" debe ser un arreglo.',
                'ubicacion_id.min' => 'Debes ingresar al menos una ubicación.',
                'ubicacion_id.*.exists' => 'La ubicación seleccionada no existe en la base de datos.',

            ]);



            if (isset($validationData)) {
                // Después de la validación, si falla, puedes ver los errores de esta manera
                return redirect()->back()->withErrors($validatedData)->withInput();

            }
            // Crear la entrada (albarán)
            $entrada = Entrada::create([
                'albaran' => $request->albaran ?? 'Sin albarán', // Asigna el albarán o usa el valor por defecto
                'users_id' => Auth::id(),  // Usuario logueado
                'otros' => $request->producto_otros[0] ?? null, // Usamos el campo "otros" del primer producto
            ]);

            // Crear los productos y asociarlos a la entrada
            foreach ($request->producto_nombre as $index => $nombre) {
                // Dividir el código de barras en segmentos
                $segmentos = explode('?', $request->producto_codigo[$index]);

                if (count($segmentos) !== 7) {
                    return redirect()->back()->with('error', 'El código de barras del fabricante no tiene el formato esperado.');
                }
                $estado = $request->estado[$index] ?? null;
                $fabricante = $request->fabricante[$index] ?? null;
                $otros = $request->producto_otros[$index] ?? null;

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

                // Crear el producto en la base de datos
                $producto = Producto::create([
                    'fabricante' => $fabricante,
                    'nombre' => $nombre,  // Aquí accedemos correctamente al nombre
                    'qr' => $qr,  // Aquí accedemos correctamente al QR
                    'n_colada' => $n_colada,
                    'n_paquete' => $n_paquete,
                    // 'diametro' => $diametro,
                    // 'longitud' => $longitud,
                    'ubicacion_id' => $request->ubicacion_id[$index],  // Ubicación del producto
                    'estado' => "Almacenado",
                    'otros' => $otros,
                ]);

                // Relacionar el producto con la entrada y guardar el valor de QR y ubicacion_id en la tabla pivote entrada_productos
                $entrada->productos()->attach($producto->id, [
                    'qr' => $qr,
                    'ubicacion_id' => $request->ubicacion_id[$index] ?? null,  // Usamos ubicacion_id del request si está presente
                    'albaran' => $request->albaran ?? 'Sin albarán', // Usamos albarán del request o un valor por defecto
                ]);

            }
            DB::commit();  // Confirmamos la transacción
            return redirect()->route('entradas.index')->with('success', 'Entrada registrada correctamente con ' . count($request->producto_nombre) . ' productos.');
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
            $validatedData = $request->validate([
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
                $segmentos = explode('?', $request->producto_codigo[$index]);

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
        } catch (\Exception $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error al eliminar la entrada: ' . $e->getMessage());
        }
    }
}
