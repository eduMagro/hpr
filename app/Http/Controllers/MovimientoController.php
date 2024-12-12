<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movimiento;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MovimientoController extends Controller
{
    public function index(Request $request)
    {
        // Base query con relaciones necesarias
        $query = Movimiento::with(['producto', 'usuario', 'ubicacionOrigen', 'ubicacionDestino', 'maquina']);

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
        $ubicaciones = \App\Models\Ubicacion::all();
        $maquinas = \App\Models\Maquina::all();

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
    
        // Iniciar una transacción
        DB::beginTransaction();
    
        try {
            // Obtener el producto que será movido
            $producto = Producto::findOrFail($request->producto_id);
    
            // Determinar el origen
            $origen = $producto->ubicacion_id ?? $producto->maquina_id;
            $origen_tipo = $producto->ubicacion_id ? 'ubicacion' : ($producto->maquina_id ? 'maquina' : null);
    
            // Determinar el destino
            $destino = $request->ubicacion_destino ?? $request->maquina_id;
            $destino_tipo = $request->ubicacion_destino ? 'ubicacion' : ($request->maquina_id ? 'maquina' : null);
    
            // Crear el registro del movimiento
            Movimiento::create([
                'producto_id' => $producto->id,
                'ubicacion_origen' => $producto->ubicacion_id,
                'ubicacion_destino' => $request->ubicacion_destino,
                'maquina_id' => $request->maquina_id,
                'users_id' => auth()->id(),
            ]);
    
            // Actualizar la ubicación o máquina del producto
            $producto->ubicacion_id = $request->ubicacion_destino ?: null;
            $producto->maquina_id = $request->maquina_id ?: null;
            $producto->estado = $request->ubicacion_destino ? 'almacenado' : 'consumido';
    
            // Guardar el producto
            $producto->save();
    
            // Confirmar la transacción
            DB::commit();
    
            // Redirigir con un mensaje de éxito
            return redirect()->route('movimientos.index')->with('success', 'Movimiento registrado correctamente.');
        } catch (\Throwable $e) {
            // Revertir la transacción si ocurre un error
            DB::rollBack();
    
            // Registrar el error en los logs
            Log::error('Error al registrar el movimiento', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            // Redirigir con un mensaje de error
            return redirect()->back()->with('error', 'Ocurrió un error al registrar el movimiento. Inténtalo nuevamente.' . $e);
        }
    }
    


}



