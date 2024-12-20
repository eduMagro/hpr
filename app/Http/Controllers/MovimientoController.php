<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\Ubicacion;
use App\Models\Maquina;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MovimientoController extends Controller
{
    public function index(Request $request)
    {
        // Base query con relaciones necesarias
        $query = Movimiento::with(['producto', 'usuario', 'ubicacionOrigen', 'ubicacionDestino', 'maquinaOrigen', 'maquina']);

        // Aplicar filtro por nombre si se pasa como parámetro en la solicitud
        if ($request->has('id')) {
            $id = $request->input('id');
            $query->whereHas('producto', function ($q) use ($id) {
                $q->where('id', 'like', '%' . $id . '%');
            });
        }

        // Ordenar resultados
        $sortBy = $request->input('sort_by', 'created_at'); // Criterio de ordenación (default: created_at)
        $order = $request->input('order', 'desc');         // Orden (asc o desc, default: desc)

        $query->orderBy($sortBy, $order);

        // Paginación
        $perPage = $request->input('per_page', 10);
        $registrosMovimientos = $query->paginate($perPage)->appends($request->except('page'));

        // Retornar vista con los datos paginados
        return view('movimientos.index', compact('registrosMovimientos'));
    }

    public function create()
    {
        $productos = Producto::with('ubicacion')->get();
        $ubicaciones = Ubicacion::all();
        $maquinas = Maquina::all();

        return view('movimientos.create', compact('productos', 'ubicaciones', 'maquinas'));
    }
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
            DB::transaction(function () use ($request) {
                // Obtener el producto que será movido
                $producto = Producto::findOrFail($request->producto_id);
    
                // Determinar el origen del movimiento
                $ubicacion_origen = null;
                $maquina_origen = null;
    
                if ($producto->ubicacion_id) {
                    $ubicacion_origen = $producto->ubicacion_id;
                }
    
                if ($producto->maquina_id) {
                    $maquina_origen = $producto->maquina_id;
                }
    
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
                    'maquina_origen' => $maquina_origen, // Asignar maquina_origen si aplica
                    'ubicacion_destino' => $request->ubicacion_destino,
                    'maquina_id' => $request->maquina_id,
                    'users_id' => auth()->id(),
                ]);
    
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
                        // Aunque la validación inicial asegura que la ubicación existe, se maneja por seguridad
                        $producto->estado = 'almacenado';
                    }
                } elseif ($request->maquina_id) {
                    // Si se mueve a una máquina, establecer el estado en 'fabricando'
                    $producto->estado = 'fabricando';
                }
    
                // Guardar los cambios en el producto
                $producto->save();
            });
    
            // Redirigir con un mensaje de éxito
            return redirect()->route('movimientos.index')->with('success', 'Movimiento registrado correctamente.');
        } catch (\Throwable $e) {
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



