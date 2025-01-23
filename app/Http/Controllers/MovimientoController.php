<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\Ubicacion;
use App\Models\Maquina;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MovimientoController extends Controller
{
    //------------------------------------------------ FILTROS() --------------------------------------------------------
    private function aplicarFiltros($query, Request $request)
    {

        // Filtro por 'id' si está presente
        if ($request->has('producto_id') && $request->producto_id) {
            $producto_id = $request->input('producto_id');
            $query->whereHas('producto', function ($q) use ($producto_id) { // Usar correctamente la variable
                // Hacer la búsqueda en la tabla 'productos' en la columna 'id'
                $q->where('id', 'like', '%' . $producto_id . '%');  // Filtro parcial por el ID del producto
            });
        }

        // Filtro por 'usuario' si está presente
        if ($request->has('usuario') && $request->usuario) {
            $usuario = $request->input('usuario');

            $query->whereHas('usuario', function ($q) use ($usuario) {
                $q->where('nombre', 'like', '%' . $usuario . '%');  // Filtro por el nombre de usuario
            });
        }

        return $query;
    }
    //------------------------------------------------ INDEX() --------------------------------------------------------
    public function index(Request $request)
    {
        // Base query con relaciones necesarias
        $query = Movimiento::with(['producto', 'usuario', 'ubicacionOrigen', 'ubicacionDestino', 'maquinaOrigen', 'maquina']);

        // Aplicar filtros utilizando el método 'aplicarFiltros'
        $query = $this->aplicarFiltros($query, $request);

        // Ordenar resultados
        $sortBy = $request->input('sort_by', 'created_at');  // Criterio de ordenación (default: created_at)
        $order = $request->input('order', 'desc');           // Orden (asc o desc, default: desc)

        $query->orderBy($sortBy, $order);

        // Paginación
        $perPage = $request->input('per_page', 10);
        $registrosMovimientos = $query->paginate($perPage)->appends($request->except('page'));

        // Retornar vista con los datos paginados
        return view('movimientos.index', compact('registrosMovimientos'));
    }
    //------------------------------------------------ CREATE() --------------------------------------------------------

    public function create()
    {
        $productos = Producto::with('ubicacion')->get();
        $ubicaciones = Ubicacion::all();
        $maquinas = Maquina::all();

        return view('movimientos.create', compact('productos', 'ubicaciones', 'maquinas'));
    }

    //------------------------------------------------ STORE() --------------------------------------------------------
    public function store(Request $request)
    {
        // Validar los datos del formulario
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'ubicacion_destino' => 'nullable|exists:ubicaciones,id',
            'maquina_id' => 'nullable|exists:maquinas,id',
        ], [
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'ubicacion_destino.exists' => 'La ubicación seleccionada no es válida.',
            'maquina_id.exists' => 'La máquina seleccionada no es válida.',
        ]);

        // Validar que no se seleccionen ubicación y máquina al mismo tiempo
        if ($request->ubicacion_destino && $request->maquina_id) {
            return back()->with('error', 'El producto no puede moverse a una ubicación y a una máquina al mismo tiempo.');
        }

        // Validar que al menos uno de los destinos esté seleccionado
        if (!$request->ubicacion_destino && !$request->maquina_id) {
            return back()->with('error', 'Tienes que elegir un destino.');
        }

        try {
            DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
            // Obtener el producto que será movido
            $producto = Producto::findOrFail($request->producto_id);

            // Determinar el origen del movimiento
            $ubicacion_origen = $producto->ubicacion_id ?: null;
            $maquina_origen = $producto->maquina_id ?: null;

            // Registrar en los logs los valores de origen
            Log::info('Registro de Movimiento:', [
                'producto_id' => $producto->id,
                'ubicacion_origen' => $ubicacion_origen,
                'maquina_origen' => $maquina_origen,
                'ubicacion_destino' => $request->ubicacion_destino,
                'maquina_id' => $request->maquina_id,
                'user_id' => auth()->id(),
            ]);

            // Crear el registro del movimiento
            Movimiento::create([
                'producto_id' => $producto->id,
                'ubicacion_origen' => $ubicacion_origen,
                'maquina_origen' => $maquina_origen,
                'ubicacion_destino' => $request->ubicacion_destino,
                'maquina_id' => $request->maquina_id,
                'users_id' => auth()->id(),
            ]);

            $materialesEnMaquina = collect();

            // Si el destino es una máquina, verificar materiales existentes del mismo diámetro
            if ($request->maquina_id) {
                // Obtener el diámetro del producto actual
                $diametro = $producto->diametro;

                // Buscar materiales en la misma máquina con el mismo diámetro
                $materialesEnMaquina = Producto::where('maquina_id', $request->maquina_id)
                    ->where('diametro', $diametro)
                    ->where('estado', '!=', 'consumido')
                    ->get();

                //(((SOLO SI HABIA ALGO EN LA MAQUINA, ACTIVAMOS ALERTA DE PESO SI LA HAY)))

                if (!$materialesEnMaquina->isEmpty()) {
                    foreach ($materialesEnMaquina as $material) {
                        if ($material->id == $producto->id) {
                            DB::rollback();
                            return response()->json([
                                'status' => 'error',
                                'message' => 'El material ya está en la máquina.'
                            ]);
                        } elseif ($material->estado == 'fabricando') {
                            return response()->json([
                                'status' => 'confirm',
                                'message' => 'Aún queda material en esta máquina con ese diámetro. ¿Desea continuar?'
                            ]);
                        }
                    }
                }
            }

            //(((SI NO HABIA NADA EN LA MAQUINA, EJECUTAMOS)))

            // Asignar ubicacion_id y maquina_id, o establecer en null si no están presentes
            $producto->ubicacion_id = $request->ubicacion_destino ?: null;
            $producto->maquina_id = $request->maquina_id ?: null;

            // Actualizar el estado basado en el destino
            if ($request->ubicacion_destino) {
                // Obtener la ubicación destino desde la base de datos
                $ubicacion = Ubicacion::find($request->ubicacion_destino);

                if ($ubicacion) {
                    // Verificar si la descripción contiene 'sold' (ignorando mayúsculas/minúsculas)
                    if (stripos($ubicacion->descripcion, 'sold') !== false) {
                        $producto->estado = 'ensamblando';
                    } else {
                        $producto->estado = 'almacenado';
                    }
                } else {
                    $producto->estado = 'almacenado';
                }
            } elseif ($request->maquina_id) {
                // Si se mueve a una máquina, establecer el estado en 'fabricando'
                $producto->estado = 'fabricando';
            }

            // Guardar los cambios en el producto
            $producto->save();

            DB::commit();  // Confirmamos la transacción
            // Redirigir con un mensaje de éxito
            return redirect()->route('movimientos.index')->with('success', 'Movimiento registrado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            // Registrar el error en los logs
            Log::error('Error al registrar el movimiento', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            // Redirigir con un mensaje de error genérico
            return redirect()->back()->with('error', 'Ocurrió un error al registrar el movimiento. Inténtalo nuevamente.');
        }
    }


    public function destroy($id)
    {
        // Iniciar una transacción para asegurar la integridad de los datos
        DB::beginTransaction();

        try {
            // Obtener el movimiento que será eliminado
            $movimiento = Movimiento::findOrFail($id);

            // Obtener el producto asociado al movimiento
            $producto = Producto::findOrFail($movimiento->producto_id);

            // Revertir la ubicación y máquina del producto al origen del movimiento
            $producto->ubicacion_id = $movimiento->ubicacion_origen ?: null;
            $producto->maquina_id = $movimiento->maquina_origen ?: null; // Asegúrate de tener este campo en tu modelo Movimiento

            // Actualizar el estado del producto basado en la ubicación de origen
            if ($movimiento->ubicacion_origen) {
                $ubicacion = Ubicacion::find($movimiento->ubicacion_origen);
                if ($ubicacion) {
                    if (stripos($ubicacion->descripcion, 'sold') !== false) {
                        $producto->estado = 'ensamblando';
                    } else {
                        $producto->estado = 'almacenado';
                    }
                } else {
                    // Si la ubicación de origen no se encuentra, asignar un estado predeterminado
                    $producto->estado = 'almacenado';
                }
            } elseif ($movimiento->maquina_origen) {
                // Si se movió a una máquina, revertir a la máquina de origen
                $producto->estado = 'fabricando';
            } else {
                // Si no hay información de origen, asignar un estado por defecto
                $producto->estado = 'almacenado';
            }

            // Guardar los cambios en el producto
            $producto->save();

            // Eliminar el movimiento
            $movimiento->delete();

            // Confirmar la transacción
            DB::commit();

            // Redirigir con un mensaje de éxito
            return redirect()->route('movimientos.index')->with('success', 'Movimiento eliminado correctamente.');
        } catch (\Throwable $e) {
            // Revertir la transacción si ocurre un error
            DB::rollBack();

            // Registrar el error en los logs
            Log::error('Error al eliminar el movimiento', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            // Redirigir con un mensaje de error genérico
            return redirect()->back()->with('error', 'Ocurrió un error al eliminar el movimiento. Inténtalo nuevamente.');
        }
    }
}
