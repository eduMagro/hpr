<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\Localizacion;
use App\Models\Paquete;
use App\Models\Ubicacion;
use App\Models\Maquina;
use App\Models\Alerta;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MovimientoController extends Controller
{
    //------------------------------------------------ FILTROS() --------------------------------------------------------
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('movimiento_id')) {
            $query->where('id', $request->movimiento_id);
        }

        if ($request->filled('usuario')) {
            $query->whereHas('usuario', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->usuario . '%');
            });
        }

        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->filled('paquete_id')) {
            $query->where('paquete_id', $request->paquete_id);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_finalizacion')) {
            $query->whereDate('created_at', '<=', $request->fecha_finalizacion);
        }

        return $query;
    }

    //------------------------------------------------ INDEX() --------------------------------------------------------
    public function index(Request $request)
    {
        // Obtener el usuario autenticado
        $usuario = auth()->user();

        // Base query con relaciones necesarias
        $query = Movimiento::with(['producto', 'ejecutadoPor', 'solicitadoPor', 'ubicacionOrigen', 'ubicacionDestino', 'maquinaOrigen', 'maquina']);

        // Filtrar según el rol del usuario
        if ($usuario->rol === 'operario') {
            $query->where('users_id', $usuario->id);
        }
        // Si es 'oficina', no aplicamos restricciones y puede ver todos los movimientos

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
        $paquetes = Paquete::with('ubicacion')->get();
        $ubicaciones = Ubicacion::all();
        $maquinas = Maquina::all();
        $localizaciones = Localizacion::all();
        return view('movimientos.create', compact('productos', 'paquetes', 'ubicaciones', 'maquinas', 'localizaciones'));
    }

    public function crearMovimiento(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_movimiento' => 'required',
            'producto_id' => 'required_if:tipo_movimiento,recarga_materia_prima|nullable|exists:productos,id',
            'paquete_id' => 'required_if:tipo_movimiento,paquete|nullable|exists:paquetes,id',
            'ubicacion_destino' => 'nullable|exists:ubicaciones,id',
            'maquina_id' => 'nullable|exists:maquinas,id',
        ], [
            'tipo_movimiento.required' => 'El tipo de movimiento es obligatorio.',
            'producto_id.required_if' => 'El producto es obligatorio cuando el tipo de movimiento es recarga de materia prima.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'paquete_id.required_if' => 'El paquete es obligatorio cuando el tipo de movimiento es paquete.',
            'paquete_id.exists' => 'El paquete seleccionado no existe.',
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
                switch ($request->tipo_movimiento) {
                    //__________________________________ RECARGA MATERIA PRIMA __________________________________
                    case 'recarga_materia_prima':
                        $productoReferencia = Producto::with('productoBase')->find($request->producto_id);
                        $maquina = Maquina::find($request->maquina_id);

                        $tipo = strtolower($productoReferencia->productoBase->tipo ?? 'N/A');
                        $diametro = $productoReferencia->productoBase->diametro ?? '?';
                        $longitud = $productoReferencia->productoBase->longitud ?? '?';
                        $nombreMaquina = $maquina->nombre ?? 'desconocida';

                        $descripcion = "Se solicita materia prima del tipo {$tipo} (Ø{$diametro}, {$longitud} mm) en la máquina {$nombreMaquina}";

                        Movimiento::create([
                            'tipo' => 'recarga_materia_prima',
                            'maquina_origen' => $request->maquina_id,
                            'estado' => 'pendiente',
                            'descripcion' => $descripcion,
                            'prioridad' => 1,
                            'fecha_solicitud' => now(),
                            'solicitado_por' => auth()->id(),
                        ]);
                        break;

                    //__________________________________ MOVIMIENTO PAQUETE __________________________________
                    case 'paquete':
                        $paquete = Paquete::find($request->paquete_id);

                        Movimiento::create([
                            'paquete_id' => $paquete->id,
                            'ubicacion_origen' => $paquete->ubicacion_id,
                            'maquina_origen' => $paquete->maquina_id,
                            'ubicacion_destino' => $request->ubicacion_destino,
                            'maquina_id' => null,
                            'estado' => 'pendiente',
                            'users_id' => auth()->id(),
                        ]);
                        break;

                    default:
                        throw new \Exception('Tipo de movimiento no reconocido.');
                }
            });

            return response()->json(['success' => true, 'message' => 'Movimiento registrado correctamente.']);
        } catch (\Exception $e) {
            Log::error('Error en movimiento: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //------------------------------------------------ STORE() --------------------------------------------------------
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_movimiento' => 'required',  // Validar el tipo de movimiento
            'producto_id' => 'required_if:tipo_movimiento,producto|nullable|exists:productos,id',  // Producto obligatorio si tipo_movimiento es producto
            'paquete_id' => 'required_if:tipo_movimiento,paquete|nullable|exists:paquetes,id',  // Paquete obligatorio si tipo_movimiento es paquete
            'ubicacion_destino' => 'nullable|exists:ubicaciones,id',
            'maquina_id' => 'nullable|exists:maquinas,id',
        ], [
            'tipo_movimiento.required' => 'El tipo de movimiento es obligatorio.',
            'producto_id.required_if' => 'El producto es obligatorio cuando el tipo de movimiento es producto.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'paquete_id.required_if' => 'El paquete es obligatorio cuando el tipo de movimiento es paquete.',
            'paquete_id.exists' => 'El paquete seleccionado no existe.',
            'ubicacion_destino.exists' => 'Ubicación no válida.',
            'maquina_id.exists' => 'Máquina no válida.',
        ]);

        // Si la validación falla, devuelve los errores
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
                switch ($request->tipo_movimiento) {
                    case 'recarga_materia_prima':
                        $producto = Producto::with('productoBase')->find($request->producto_id);

                        if ($request->maquina_id) {
                            $maquina = Maquina::find($request->maquina_id);
                            $maquinas_encarretado = ['MSR20', 'MS16', 'PS12', 'F12'];

                            if (in_array($maquina->codigo, $maquinas_encarretado) && $producto->productoBase->tipo == 'barras') {
                                throw new \Exception('La máquina seleccionada solo acepta productos de tipo encarretado.');
                            }

                            $diametro = $producto->productoBase->diametro;

                            if ($diametro < $maquina->diametro_min || $diametro > $maquina->diametro_max) {
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
                    case 'paquete':
                        $paquete = Paquete::find($request->paquete_id);

                        $movimiento = Movimiento::create([
                            'paquete_id' => $request->paquete_id,
                            'ubicacion_origen' => $paquete->ubicacion_id,
                            'maquina_origen' => $paquete->maquina_id, // Si aplicable
                            'ubicacion_destino' => $request->ubicacion_destino,
                            'maquina_id' => NULL,
                            'users_id' => auth()->id(),
                        ]);
                        $producto = $movimiento->producto;
                        $diametro = number_format($producto->diametro, 2);
                        $maquina = Maquina::find($movimiento->maquina_id);

                        // Buscar alerta pendiente para ese diámetro y máquina
                        $alerta = Alerta::where('completada', false)
                            ->where('mensaje', 'like', "%diámetro {$diametro}%")
                            ->where('mensaje', 'like', "%máquina {$maquina->nombre}%")
                            ->latest()
                            ->first();

                        if ($alerta) {
                            $alerta->completada = true;
                            $alerta->save();
                        }
                        // Actualización de ubicación del paquete
                        $paquete->ubicacion_id = $request->ubicacion_destino ?: null;
                        $paquete->save();

                    default:
                        throw new \Exception('Tipo de movimiento no reconocido.');
                }
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
            if ($movimiento->producto_id) {
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
            }

            if ($movimiento->paquete_id) {
                // Si es un paquete, proceder con la lógica para paquetes
                $paquete = Paquete::findOrFail($movimiento->paquete_id);

                // Revertir la ubicación del paquete al origen del movimiento
                $paquete->ubicacion_id = $movimiento->ubicacion_origen ?: null;

                // Guardar los cambios en el paquete
                $paquete->save();
            }

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
