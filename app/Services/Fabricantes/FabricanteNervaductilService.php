<?php

namespace App\Services\Fabricantes;

use App\Models\Entrada;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Validation\ValidationException;

class FabricanteNervaductilService implements FabricanteServiceInterface
{
    public function procesar(Request $request): void
    {
        DB::beginTransaction();
       
        try {
            $request->validate([
                'albaran' => 'nullable|string|max:255',
                'peso_total' => 'nullable|numeric|min:1',
                'cantidad_productos' => 'required|integer|min:1|max:30',
                'fabricante' => 'nullable|string',
                'fabricante.*' => 'nullable|string|max:255',
                'producto_nombre' => 'required|array|min:1',
                'producto_nombre.*' => 'required|string|max:255',
                'producto_codigo' => 'required|array|min:1',
                'producto_codigo.*' => 'required|string|max:10',
                'diametro' => 'required|array|min:1',
                'diametro.*' => 'required|numeric|min:0',
                'longitud' => 'nullable|array',
                'longitud.*' => 'nullable|numeric|min:0',
                'n_colada' => 'nullable|array',
                'n_colada.*' => 'nullable|string|max:255',
                'producto_peso' => 'required|array|min:1',
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
                'cantidad_productos.required' => 'El campo "cantidad de productos" es obligatorio.',
                'cantidad_productos.integer' => 'El campo "cantidad de productos" debe ser un número entero.',
                'cantidad_productos.min' => 'El campo "cantidad de productos" debe ser al menos 1.',
                'cantidad_productos.max' => 'El campo "cantidad de productos" no puede ser mayor de 30.',
                'fabricante.string' => 'El campo "fabricante" debe ser un string.',
                'fabricante.*.string' => 'Cada fabricante debe ser una cadena de texto.',
                'producto_nombre.required' => 'El campo "nombre del producto" es obligatorio.',
                'producto_nombre.array' => 'El campo "nombre del producto" debe ser un arreglo.',
                'producto_nombre.*.string' => 'Cada nombre de producto debe ser una cadena de texto.',
                'producto_nombre.*.max' => 'Cada nombre de producto no puede tener más de 255 caracteres.',
                'producto_codigo.required' => 'El campo "código del producto" es obligatorio.',
                'producto_codigo.array' => 'El campo "código del producto" debe ser un arreglo.',
                'producto_codigo.*.string' => 'Cada código de producto debe ser una cadena de texto.',
                'producto_codigo.*.max' => 'Cada código de producto no puede tener más de 255 caracteres.',
                'diametro.required' => 'El campo "diámetro" es obligatorio.',
                'diametro.array' => 'El campo "diámetro" debe ser un arreglo.',
                'diametro.*.numeric' => 'Cada valor de "diámetro" debe ser un número.',
                'diametro.*.min' => 'El valor de "diámetro" debe ser al menos 0.',
                'longitud.array' => 'El campo "longitud" debe ser un arreglo.',
                'longitud.*.numeric' => 'Cada valor de "longitud" debe ser un número.',
                'longitud.*.min' => 'El valor de "longitud" debe ser al menos 0.',
                'n_paquete.*.max' => 'Cada valor de "nº paquete" no puede tener más de 255 caracteres.',
                'n_colada.array' => 'El campo "nº colada" debe ser un arreglo.',
                'n_colada.*.string' => 'Cada valor de "nº colada" debe ser una cadena de texto.',
                'n_colada.*.max' => 'Cada valor de "nº colada" no puede tener más de 255 caracteres.',
                'producto_peso.required' => 'El campo "peso inicial" es obligatorio.',
                'producto_peso.array' => 'El campo "peso inicial" debe ser un arreglo.',
                'producto_peso.*.required' => 'Cada valor de "peso inicial" es obligatorio.',
                'producto_peso.*.numeric' => 'Cada valor de "peso inicial" debe ser un número.',
                'producto_peso.*.min' => 'Cada valor de "peso inicial" debe ser al menos 0.',
                'producto_otros.array' => 'El campo "otros" debe ser un arreglo.',
                'producto_otros.*.string' => 'Cada valor en "otros" debe ser una cadena de texto.',
                'producto_otros.*.max' => 'Cada valor en "otros" no puede tener más de 255 caracteres.',
                'ubicacion_id.required' => 'El campo "ubicación" es obligatorio.',
                'ubicacion_id.array' => 'El campo "ubicación" debe ser un arreglo.',
                'ubicacion_id.*.exists' => 'La ubicación seleccionada no existe.',
                'tipo_producto.required' => 'El campo "tipo de producto" es obligatorio.',
                'tipo_producto.array' => 'El campo "tipo de producto" debe ser un arreglo.',
                'tipo_producto.*.string' => 'Cada tipo de producto debe ser una cadena de texto.',
                'tipo_producto.*.in' => 'Cada tipo de producto debe ser "encarretado" o "barra".',
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
                // Recuperar y validar los datos del producto
                $codigoNerva = $request->producto_codigo[$index] ?? null;
                $tipoProducto = $request->tipo_producto[$index] ?? null;
                $diametro = $request->diametro[$index] ?? null;
                $longitud = $request->longitud[$index] ?? null;
                //$nPaquete = $request->n_paquete[$index] ?? null;
                $nColada = $request->n_colada[$index] ?? null;
                $pesoInicial = $request->producto_peso[$index] ?? null;
                $pesoStock = $request->producto_peso[$index] ?? null;
                $ubicacionId = $request->ubicacion_id[$index] ?? null;

                $nPaquete = substr($request->producto_codigo[$index], -4);

                // Validar datos obligatorios
                if (is_null($codigoNerva) || is_null($tipoProducto) || is_null($diametro) || is_null($nPaquete) || is_null($pesoInicial) || is_null($ubicacionId)) {
                    throw new Exception("Datos incompletos para el producto en el índice {$index}");
                }

                // Validar formato del código de barras
                if (!preg_match('/^\d{10}$/', $codigoNerva)) {
                    throw new Exception("El código de barras del fabricante no tiene el formato esperado en el índice {$index}. Debe ser un número de 9 cifras.");
                }

                // Crear el producto
                $producto = Producto::create([
                    'fabricante' => 'Nervaductil',
                    'nombre' => $nombre,
                    'tipo' => $tipoProducto,
                    'diametro' => $diametro,
                    'longitud' => $longitud,
                    'n_colada' => $nColada,
                    'n_paquete' => $nPaquete,
                    'peso_inicial' => $pesoInicial,
                    'peso_stock' => $pesoStock,
                    'ubicacion_id' => $ubicacionId,
                    'estado' => 'Almacenado',
                    'otros' => $request->producto_otros[$index] ?? null,
                ]);

                // Asociar el producto con la entrada
                $entrada->productos()->attach($producto->id, [
                    'ubicacion_id' => $ubicacionId,
                    'users_id' => Auth::id(),
                ]);
            }

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error('Error al procesar el fabricante Nervaductil', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
                'index' => $index ?? null,
            ]);

            throw new Exception('Error al procesar el fabricante Nervaductil: ' . $e->getMessage());
        }
    }
}
