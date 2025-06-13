<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\ProductoBase;
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

        // 👉 Redirigir a 'create' si el usuario es operario
        if ($usuario->rol === 'operario') {
            return redirect()->route('movimientos.create');
        }
        // Base query con relaciones necesarias
        $query = Movimiento::with(['producto', 'productoBase', 'ejecutadoPor', 'solicitadoPor', 'ubicacionOrigen', 'ubicacionDestino', 'maquinaOrigen', 'maquinaDestino']);
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
            'tipo' => 'required',
            'producto_id' => 'nullable|exists:productos,id',
            'producto_base_id' => 'required_if:tipo,recarga_materia_prima|exists:productos_base,id',
            'paquete_id' => 'required_if: tipo,paquete|nullable|exists:paquetes,id',
            'ubicacion_destino' => 'nullable|exists:ubicaciones,id',
            'maquina_id' => 'nullable|exists:maquinas,id',
        ], [
            'tipo.required' => 'El tipo de movimiento es obligatorio.',
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
                switch ($request->tipo) {
                    //__________________________________ RECARGA MATERIA PRIMA __________________________________
                    case 'recarga_materia_prima':
                        $productoBase = null;

                        if ($request->filled('producto_id')) {
                            $productoReferencia = Producto::with('productoBase')->find($request->producto_id);
                            $productoBase = $productoReferencia->productoBase;
                        } else {
                            $productoBase = ProductoBase::find($request->producto_base_id);
                        }

                        $maquina = Maquina::find($request->maquina_id);

                        $tipo = strtolower($productoBase->tipo ?? 'N/A');
                        $diametro = $productoBase->diametro ?? '?';
                        $longitud = $productoBase->longitud ?? '?';

                        $nombreMaquina = $maquina->nombre ?? 'desconocida';

                        $descripcion = "Se solicita materia prima del tipo {$tipo} (Ø{$diametro}, {$longitud} mm) en la máquina {$nombreMaquina}";

                        Movimiento::create([
                            'tipo'              => 'Recarga materia prima',
                            'maquina_origen'    => null,
                            'maquina_destino'   => $request->maquina_id,
                            'producto_id'       => $request->producto_id, // puede ser null
                            'producto_base_id'  => $productoBase->id,
                            'estado'            => 'pendiente',
                            'descripcion'       => $descripcion,
                            'prioridad'         => 1,
                            'fecha_solicitud'   => now(),
                            'solicitado_por'    => auth()->id(),
                        ]);

                        break;


                    //__________________________________ MOVIMIENTO PAQUETE __________________________________
                    case 'paquete':
                        $paquete = Paquete::findOrFail($request->paquete_id);

                        $nombreUbicacion = optional($paquete->ubicacion)->nombre ?? 'desconocida';
                        $descripcion = "Se solicita mover el paquete #{$paquete->codigo} desde {$nombreUbicacion}";

                        Movimiento::create([
                            'tipo'               => 'Movimiento de paquete',
                            'paquete_id'         => $paquete->id,
                            'ubicacion_origen'   => $paquete->ubicacion_id,
                            'maquina_origen'     => $paquete->maquina_id,
                            'estado'             => 'pendiente',
                            'prioridad'          => 3,
                            'fecha_solicitud'    => now(),
                            'solicitado_por'     => auth()->id(),
                            'descripcion'        => $descripcion,
                        ]);
                        break;


                    default:
                        throw new \Exception('Tipo de movimiento no reconocido.');
                }
            });

            return redirect()->back()->with('success', 'Movimiento creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al registrar movimiento: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Hubo un problema al registrar el movimiento. Inténtalo de nuevo.');
        }
    }

    //------------------------------------------------ STORE() --------------------------------------------------------
    public function store(Request $request)
    {
        $tipoMovimiento = $request->tipo;

        $validator = Validator::make($request->all(), [
            'codigo_general'    => 'required|string|max:50',
            'ubicacion_destino' => 'nullable|exists:ubicaciones,id',
            'maquina_destino'   => 'nullable|exists:maquinas,id',
        ], [
            'codigo_general.required'    => 'Debes escanear un código.',
            'ubicacion_destino.exists'   => 'Ubicación no válida.',
            'maquina_destino.exists'     => 'Máquina no válida.',
        ]);

        if ($validator->fails()) {
            Log::error('❌ Validación fallida:', $validator->errors()->toArray());
            return back()->withErrors($validator)->withInput();
        }

        // ------------------------------------------------------------------------------------------------
        // 📦 Preparar datos base
        // ------------------------------------------------------------------------------------------------
        $codigo = strtoupper($request->codigo_general);

        $maquinaId = $request->maquina_destino;

        $ubicacionId = $request->ubicacion_destino;

        $esRecarga = $maquinaId !== null;

        $maquinaDetectada = $esRecarga
            ? Maquina::findOrFail($maquinaId)
            : null;

        $ubicacion = !$esRecarga
            ? Ubicacion::findOrFail($ubicacionId)
            : null;

        // Si no hay máquina explícita pero la ubicación coincide con alguna máquina por nombre
        if (!$maquinaDetectada && $ubicacion) {
            $maquinaDetectada = Maquina::where('nombre', $ubicacion->nombre)->first();
        }


        try {
            DB::transaction(function () use ($codigo, $ubicacion, $maquinaDetectada) {
                $producto = null;
                $paquete = null;

                if (str_starts_with($codigo, 'MP')) {
                    $producto = Producto::with('productoBase', 'ubicacion')->where('codigo', $codigo)->firstOrFail();

                    $tipoMovimiento = 'producto';
                } elseif (str_starts_with($codigo, 'P')) {
                    $paquete = Paquete::with('ubicacion')->where('codigo', $codigo)->firstOrFail();
                    $tipoMovimiento = 'paquete';
                } else {
                    throw new \Exception('El código escaneado no es válido. Debe comenzar por MP- o P-.');
                }

                //------------------------------ TIPO MOVIMIENTO PRODUCTO --------------------------
                if ($tipoMovimiento === 'producto') {

                    $tipoBase = strtolower($producto->productoBase->tipo);

                    $descripcion = "Pasamos {$tipoBase} Ø{$producto->productoBase->diametro} mm"
                        . " L:{$producto->productoBase->longitud} mm"
                        . " de " . ($producto->ubicacion->nombre ?? 'origen desconocido')
                        . " a " . ($maquinaDetectada
                            ? 'máquina ' . $maquinaDetectada->nombre
                            : 'ubicación ' . $ubicacion->nombre);

                    // Validaciones si hay máquina detectada
                    if ($maquinaDetectada) {

                        $maquinasEncarretado = ['MSR20', 'MS16', 'PS12', 'F12'];
                        if (in_array($maquinaDetectada->codigo, $maquinasEncarretado) && $tipoBase === 'barras') {
                            throw new \Exception('La máquina seleccionada solo acepta productos de tipo encarretado.');
                        }

                        $diametro = $producto->productoBase->diametro;
                        if ($diametro < $maquinaDetectada->diametro_min || $diametro > $maquinaDetectada->diametro_max) {
                            throw new \Exception('El diámetro del producto no está dentro del rango aceptado por la máquina.');
                        }

                        // 🔄 Recarga: buscar movimiento pendiente para esta máquina y base
                        $movimientoPendiente = Movimiento::where('producto_base_id', $producto->producto_base_id)
                            ->where('maquina_destino', $maquinaDetectada->id)
                            ->where('estado', 'pendiente')
                            ->latest()
                            ->first();


                        if ($movimientoPendiente) {
                            $movimientoPendiente->update([
                                'producto_id'        => $producto->id,
                                'ubicacion_origen'   => $producto->ubicacion_id,
                                'estado'             => 'completado',
                                'fecha_ejecucion'    => now(),
                                'ejecutado_por'      => auth()->id(),
                            ]);
                        } else {
                            Movimiento::create([
                                'tipo'               => 'movimiento libre',
                                'producto_id'        => $producto->id,
                                'producto_base_id'   => $producto->producto_base_id,
                                'ubicacion_origen'   => $producto->ubicacion_id,
                                'maquina_origen'     => $producto->maquina_id,
                                'ubicacion_destino'  => $ubicacion->id,
                                'maquina_destino'    => null,
                                'estado'             => 'completado',
                                'descripcion'        => $descripcion,
                                'fecha_ejecucion'    => now(),
                                'ejecutado_por'      => auth()->id(),
                            ]);
                        }

                        // Cambiar estado del producto actual
                        $producto->update([
                            'ubicacion_id' => null,
                            'maquina_id'   => $maquinaDetectada->id,
                            'estado'       => 'fabricando',
                        ]);

                        // Consumir producto anterior si hay otro en esa máquina
                        $productoAnterior = Producto::where('producto_base_id', $producto->producto_base_id)
                            ->where('id', '!=', $producto->id)
                            ->where('maquina_id', $maquinaDetectada->id)
                            ->where('estado', 'fabricando')
                            ->latest('updated_at')
                            ->first();

                        if ($productoAnterior) {
                            $productoAnterior->update([
                                'maquina_id' => null,
                                'estado' => 'consumido',
                            ]);
                        }
                    } else {
                        // Movimiento normal a ubicación
                        Movimiento::create([
                            'tipo'               => 'movimiento libre',
                            'producto_id'        => $producto->id,
                            'producto_base_id'   => $producto->producto_base_id,
                            'ubicacion_origen'   => $producto->ubicacion_id,
                            'maquina_origen'     => $producto->maquina_id,
                            'ubicacion_destino'  => $ubicacion->id,
                            'maquina_destino'    => null,
                            'estado'             => 'completado',
                            'descripcion'        => $descripcion,
                            'fecha_ejecucion'    => now(),
                            'ejecutado_por'      => auth()->id(),
                        ]);

                        $producto->update([
                            'ubicacion_id' => $ubicacion->id,
                            'maquina_id'   => null,
                            'estado'       => 'almacenado',
                        ]);
                    }
                }
                //------------------------------ TIPO MOVIMIENTO PAQUETE --------------------------
                if ($tipoMovimiento === 'paquete') {
                    $descripcion = "Movemos paquete de " . ($paquete->ubicacion->nombre ?? 'origen desconocido')
                        . " a " . $ubicacion->nombre;

                    // 🔍 Buscar si ya hay un movimiento pendiente para este paquete y destino
                    $movimientoPendiente = Movimiento::where('paquete_id', $paquete->id)
                        ->where(function ($query) use ($ubicacion, $maquinaDetectada) {
                            if ($ubicacion) {
                                $query->where('ubicacion_destino', $ubicacion->id);
                            }
                            if ($maquinaDetectada) {
                                $query->orWhere('maquina_destino', $maquinaDetectada->id);
                            }
                        })
                        ->where('estado', 'pendiente')
                        ->latest()
                        ->first();

                    if ($movimientoPendiente) {
                        $movimientoPendiente->update([
                            'estado'           => 'completado',
                            'fecha_ejecucion'  => now(),
                            'ejecutado_por'    => auth()->id(),
                        ]);
                    } else {
                        Movimiento::create([
                            'tipo'               => 'movimiento libre',
                            'paquete_id'         => $paquete->id,
                            'ubicacion_origen'   => $paquete->ubicacion_id,
                            'maquina_origen'     => $paquete->maquina_id,
                            'ubicacion_destino'  => $ubicacion->id,
                            'maquina_destino'    => $maquinaDetectada?->id,
                            'estado'             => 'completado',
                            'descripcion'        => $descripcion,
                            'fecha_ejecucion'    => now(),
                            'ejecutado_por'      => auth()->id(),
                        ]);
                    }

                    // 📦 Actualizar ubicación y máquina del paquete
                    $paquete->update([
                        'ubicacion_id' => $ubicacion->id,
                        'maquina_id'   => $maquinaDetectada?->id,
                    ]);
                }
            });

            return back()->with('success', 'Movimiento registrado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al registrar movimiento: ' . $e->getMessage());
            return back()->with('error', 'Hubo un problema al registrar el movimiento: ' . $e->getMessage());
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
