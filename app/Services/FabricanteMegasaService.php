
<?php 
namespace App\Services\Fabricantes;

use App\Models\Entrada;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Validation\ValidationException;

class FabricanteMegasaService implements FabricanteServiceInterface
{
    public function procesar(Request $request) :void
    {
        DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
        try {
            // Validar los datos enviados
            $validatedData = $request->validate([
                'albaran' => 'nullable|string|max:255',
                'peso_total' => 'nullable|numeric|min:1',
                'fabricante' => 'nullable|array',
                'fabricante.*' => 'nullable|string|max:255',
                'producto_nombre' => 'required|array|min:1',
                'producto_nombre.*' => 'required|string|max:255',
                'producto_codigo' => 'required|array|min:1',
                'producto_codigo.*' => 'required|string|max:255',
                'producto_peso' => 'required|array',
                'producto_peso.*' => 'required|numeric|min:0',
                'producto_otros' => 'nullable|array',
                'producto_otros.*' => 'nullable|string|max:255',
                'ubicacion_id' => 'required|array|min:1',
                'ubicacion_id.*' => 'required|exists:ubicaciones,id',
                'tipo_producto' => 'required|array|min:1',
                'tipo_producto.*' => 'required|string|in:encarretado,barra',
            ], [
                'albaran.string' => 'El campo "albarán" debe ser una cadena de texto.',
                'albaran.max' => 'El campo "albarán" no puede tener más de 255 caracteres.',

                'peso_total.numeric' => 'El campo "peso total" debe ser un valor numérico.',
                'peso_total.min' => 'El campo "peso total" debe ser al menos 1.',

                'fabricante.array' => 'El campo "fabricante" debe ser un arreglo.',
                'fabricante.*.string' => 'Cada fabricante debe ser una cadena de texto.',
                'fabricante.*.max' => 'Cada fabricante no puede tener más de 255 caracteres.',

                'producto_nombre.required' => 'El campo "nombre del producto" es obligatorio.',
                'producto_nombre.array' => 'El campo "nombre del producto" debe ser un arreglo.',
                'producto_nombre.min' => 'Debes ingresar al menos un nombre de producto.',
                'producto_nombre.*.string' => 'Cada nombre de producto debe ser una cadena de texto.',
                'producto_nombre.*.max' => 'Cada nombre de producto no puede tener más de 255 caracteres.',

                'producto_codigo.required' => 'El campo "código del producto" es obligatorio.',
                'producto_codigo.array' => 'El campo "código del producto" debe ser un arreglo.',
                'producto_codigo.min' => 'Debes ingresar al menos un código de producto.',
                'producto_codigo.*.string' => 'Cada código de producto debe ser una cadena de texto.',
                'producto_codigo.*.max' => 'Cada código de producto no puede tener más de 255 caracteres.',

                'producto_peso.required' => 'El campo "peso del producto" es obligatorio.',
                'producto_peso.array' => 'El campo "peso del producto" debe ser un arreglo.',
                'producto_peso.*.numeric' => 'Cada peso del producto debe ser un valor numérico.',
                'producto_peso.*.min' => 'El peso del producto no puede ser negativo.',

                'producto_otros.array' => 'El campo "otros detalles" debe ser un arreglo.',
                'producto_otros.*.string' => 'Cada detalle debe ser una cadena de texto.',
                'producto_otros.*.max' => 'Cada detalle no puede tener más de 255 caracteres.',

                'ubicacion_id.required' => 'El campo "ubicación" es obligatorio.',
                'ubicacion_id.array' => 'El campo "ubicación" debe ser un arreglo.',
                'ubicacion_id.min' => 'Debes ingresar al menos una ubicación.',
                'ubicacion_id.*.exists' => 'La ubicación seleccionada no existe en la base de datos.',

                'tipo_producto.required' => 'El campo "tipo de producto" es obligatorio.',
                'tipo_producto.array' => 'El campo "tipo de producto" debe ser un arreglo.',
                'tipo_producto.min' => 'Debes ingresar al menos un tipo de producto.',
                'tipo_producto.*.required' => 'Cada tipo de producto es obligatorio.',
                'tipo_producto.*.string' => 'Cada tipo de producto debe ser una cadena de texto.',
                'tipo_producto.*.in' => 'Cada tipo de producto debe ser "encarretado" o "barra".',
            ]);

            // Calcular el peso total desde los productos
            // $peso_total_calculado = 0;
            // if ($request->producto_peso) {
            //     foreach ($request->producto_peso as $peso) {
            //         $peso_total_calculado += $peso;
            //     }
            // }
            $peso_total_calculado = array_sum($request->producto_peso);

            // Verificar que el peso total calculado coincida con el peso_total recibido
            if ($request->peso_total && $peso_total_calculado != $request->peso_total) {
                DB::rollBack();  // Si no coincide, revertimos la transacción
                throw new Exception('El peso de los materiales (' . $peso_total_calculado . ' kg) no coincide con el peso total del camión (' . $request->peso_total . ' kg).');
            }

            // Crear la entrada (albarán)
            $entrada = Entrada::create([
                'albaran' => $request->albaran ?? 'Sin albarán', // Asigna el albarán o usa el valor por defecto
                'peso_total' => $request->peso_total,
                'users_id' => Auth::id(),  // Usuario logueado
                'otros' => $request->producto_otros[0] ?? null, // Usamos el campo "otros" del primer producto
            ]);

            // Crear los productos y asociarlos a la entrada
            foreach ($request->producto_nombre as $index => $nombre) {
                // Dividir el código de barras en segmentos
                $segmentos = explode('?', $request->producto_codigo[$index]);

                if (count($segmentos) !== 7) {
                    throw new Exception('El código de barras del fabricante no tiene el formato esperado.');
                }

                $fabricante = $request->fabricante[$index] ?? null;
                $otros = $request->producto_otros[$index] ?? null;

                // Extraer datos del código de barras
                $n_colada = $segmentos[1] ?? null;
                $n_paquete = $segmentos[2] ?? null;

                if ($request->tipo_producto[$index] === 'barra') {
                    // Para barras:
                    // - El diámetro se extrae del segmento 4 (quitando la D)
                    // - La longitud se extrae del segmento 5 (quitando la L)
                    $diametro = isset($segmentos[4]) ? str_replace('D', '', $segmentos[4]) : null;
                    $longitud = isset($segmentos[5]) ? str_replace('L', '', $segmentos[5]) : null;
                } elseif ($request->tipo_producto[$index] === 'encarretado') {
                    // Para encarretado:
                    // - El diámetro se extrae del segmento 5 (quitando la D)
                    // - No hay longitud, por lo que la dejamos en null
                    $diametro = isset($segmentos[5]) ? str_replace('D', '', $segmentos[5]) : null;
                    $longitud = null;
                } else {
                    // En caso de que el tipo de producto no sea ni 'barra' ni 'encarretado', puedes manejarlo de otra forma:
                    $diametro = null;
                    $longitud = null;
                }
                // Convertir a número y dividir por 100 si no son nulos
                if (!is_null($diametro)) {
                    $diametro = (float) $diametro / 100;
                }

                if (!is_null($longitud)) {
                    $longitud = (float) $longitud / 100;
                }

                // Crear el producto en la base de datos
                $producto = Producto::create([
                    'fabricante' => $fabricante,
                    'nombre' => $nombre,  // Aquí accedemos correctamente al nombre
                    'tipo' => $request->tipo_producto[$index], // Guardamos el tipo de producto
                    'diametro' => $diametro,
                    'longitud' => $longitud,
                    'n_colada' => $n_colada,
                    'n_paquete' => $n_paquete,
                    'peso_inicial' => $request->producto_peso[$index],
                    'peso_stock' => $request->producto_peso[$index],
                    'ubicacion_id' => $request->ubicacion_id[$index],  // Ubicación del producto
                    'estado' => "Almacenado",
                    'otros' => $otros,
                ]);

                // Relacionar el producto con la entrada y guardar el valor de QR y ubicacion_id en la tabla pivote entrada_productos
                $entrada->productos()->attach($producto->id, [
                    'ubicacion_id' => $request->ubicacion_id[$index] ?? null,  // Usamos ubicacion_id del request si está presente
                    'users_id' => Auth::id(),
                ]);
            }
            
            DB::commit();  // Confirmamos la transacción
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e; // Deja que el controlador maneje los errores de validación
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error al procesar el fabricante Megasa: ' . $e->getMessage());
        }
    }
}
