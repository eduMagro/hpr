<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\Ubicacion;
use App\Models\Maquina;
use Illuminate\Support\Facades\Validator;
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
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:productos,id',
            'ubicacion_destino' => 'nullable|exists:ubicaciones,id',
            'maquina_id' => 'nullable|exists:maquinas,id',
        ], [
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'ubicacion_destino.exists' => 'Ubicación no válida.',
            'maquina_id.exists' => 'Máquina no válida.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->ubicacion_destino && $request->maquina_id) {
            return response()->json(['error' => 'No puedes elegir una ubicación y una máquina a la vez como destino']);
        }

        if (!$request->ubicacion_destino && !$request->maquina_id) {
            return response()->json(['error' => 'No has elegido destino']);
        }

        try {
            DB::transaction(function () use ($request) {
                $producto = Producto::find($request->producto_id);

                if ($request->maquina_id) {
                    $maquina = Maquina::find($request->maquina_id);
                    $maquinas_encarretado = ['MSR20', 'MS16', 'PS12', 'F12'];

                    if (in_array($maquina->codigo, $maquinas_encarretado) && !$producto->es_encarretado) {
                        throw new \Exception('La máquina seleccionada solo acepta productos de tipo encarretado.');
                    }

                    if ($producto->diametro < $maquina->diametro_min || $producto->diametro > $maquina->diametro_max) {
                        throw new \Exception('El diámetro del producto no está dentro del rango aceptado por la máquina.');
                    }
                }

                Movimiento::create([
                    'producto_id' => $producto->id,
                    'ubicacion_origen' => $producto->ubicacion_id,
                    'maquina_origen' => $producto->maquina_id,
                    'ubicacion_destino' => $request->ubicacion_destino,
                    'maquina_id' => $request->maquina_id,
                    'users_id' => auth()->id(),
                ]);

                $producto->ubicacion_id = $request->ubicacion_destino ?: null;
                $producto->maquina_id = $request->maquina_id ?: null;
                $producto->estado = $request->ubicacion_destino ? 'almacenado' : 'fabricando';
                $producto->save();
            });

            return response()->json(['success' => true, 'message' => 'Movimiento registrado correctamente.']);
        } catch (\Exception $e) {
            Log::error('Error en movimiento: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
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
