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

    
    public function procesar(Request $request): void
    {
        DB::beginTransaction();

        try {
            //Validar los datos del request
            $request->validate([
                'albaran' => 'required|string|max:255',
                'peso_total' => 'nullable|numeric|min:1',
                'cantidad_productos' => 'required|integer|min:1|max:30',
                'fabricante' => 'nullable|string',
                'fabricante.*' => 'nullable|string|max:255',
                'producto_nombre' => 'required|array|min:1',
                'producto_nombre.*' => 'required|string|max:255',
<<<<<<< HEAD
                'producto_codigo' => 'required|array|min:80',
                'producto_codigo.*' => 'required|string|max:90',
=======
                'producto_codigo' => 'required|array|min:1',
                'producto_codigo.*' => 'required|string|max:100',
>>>>>>> 6fea693 (primercommit)
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
                'albaran.required' => 'El campo "albarán" no puede ser nulo.',
                'peso_total.numeric' => 'El campo "peso total" debe ser un valor numérico.',
                'peso_total.min' => 'El campo "peso total" debe ser al menos 1.',
                'fabricante.string' => 'El campo "fabricante" debe ser un string.',
                'fabricante.*.string' => 'Cada fabricante debe ser una cadena de texto.',
                'producto_nombre.required' => 'El campo "nombre del producto" es obligatorio.',
                'producto_nombre.array' => 'El campo "nombre del producto" debe ser un arreglo.',
                'producto_codigo.required' => 'El campo "código del producto" es obligatorio.',
                'producto_codigo.array' => 'El campo "código del producto" debe ser un arreglo.',
<<<<<<< HEAD
=======
				'producto_codigo.min' => 'El campo "códig del producto" parece incorrecto.',
				'producto_codigo.max' => 'El campo "códig del producto" parece incorrecto.',
>>>>>>> 6fea693 (primercommit)
                'producto_peso.required' => 'El campo "peso del producto" es obligatorio.',
                'producto_peso.array' => 'El campo "peso del producto" debe ser un arreglo.',
                'ubicacion_id.required' => 'El campo "ubicación" es obligatorio.',
                'ubicacion_id.exists' => 'La ubicación no existe.',
                'tipo_producto.required' => 'El campo "tipo de producto" es obligatorio.',
                'tipo_producto.array' => 'El campo "tipo de producto" debe ser un arreglo.',
            ]);

            // Calcular el peso total de los productos
            $peso_total_calculado = array_sum($request->producto_peso);

            // Verificar si el peso total coincide
            if ($request->peso_total && $peso_total_calculado != $request->peso_total) {
                throw new Exception('El peso de los materiales (' . $peso_total_calculado . ' kg) no coincide con el peso total del camión (' . $request->peso_total . ' kg).');
            }

            // Crear la entrada (albarán)
            $entrada = Entrada::create([
                'albaran' => $request->albaran ?? 'Sin albarán',
                'peso_total' => $request->peso_total,
                'users_id' => Auth::id(),
                'otros' => $request->producto_otros[0] ?? null,
            ]);

            // Crear los productos y asociarlos a la entrada
            foreach ($request->producto_nombre as $index => $nombre) {
                // Dividir el código de barras en segmentos
                $segmentos = explode('_', $request->producto_codigo[$index]);

                if (count($segmentos) !== 7) {
                    throw new Exception('El código de barras del fabricante no tiene el formato esperado.');
                }

                // Extraer datos del código de barras
                $n_colada = $segmentos[1] ?? null;
                $n_paquete = $segmentos[2] ?? null;

                $diametro = null;
                $longitud = null;

                if ($request->tipo_producto[$index] === 'barra') {
                    $diametro = isset($segmentos[4]) ? str_replace('D', '', $segmentos[4]) : null;
                    $longitud = isset($segmentos[5]) ? str_replace('L', '', $segmentos[5]) : null;
                } elseif ($request->tipo_producto[$index] === 'encarretado') {
                    $diametro = isset($segmentos[5]) ? str_replace('D', '', $segmentos[5]) : null;
                }

                // Normalizar los valores numéricos
                $diametro = $diametro !== null ? (float) $diametro / 100 : null;
                $longitud = $longitud !== null ? (float) $longitud / 100 : null;

                // Crear el producto
                $producto = Producto::create([
                    'fabricante' => 'Megasa',
                    'nombre' => $nombre,
                    'tipo' => $request->tipo_producto[$index],
                    'diametro' => $diametro,
                    'longitud' => $longitud,
                    'n_colada' => $n_colada,
                    'n_paquete' => $n_paquete,
                    'peso_inicial' => $request->producto_peso[$index],
                    'peso_stock' => $request->producto_peso[$index],
                    'ubicacion_id' => $request->ubicacion_id[$index],
                    'estado' => 'Almacenado',
                    'otros' => $request->producto_otros[$index] ?? null,
                ]);

                // Asociar el producto con la entrada
                $entrada->productos()->attach($producto->id, [
                    'ubicacion_id' => $request->ubicacion_id[$index] ?? null,
                    'users_id' => Auth::id(),
                ]);
            }

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e; // Deja que el controlador maneje errores de validación
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error al procesar el fabricante Megasa: ' . $e->getMessage());
        }
    }
}
