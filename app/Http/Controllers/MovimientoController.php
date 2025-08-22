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
use Termwind\Components\Dd;

class MovimientoController extends Controller
{
    //------------------------------------------------ FILTROS() --------------------------------------------------------
    private function aplicarFiltros($query, Request $request)
    {
        /* ─────────────── Filtros directos por columna ─────────────── */

        // ID de movimiento
        if ($request->filled('id') || $request->filled('movimiento_id')) {
            $query->where('id', $request->id ?? $request->movimiento_id);
        }

        // Tipo de movimiento
        if ($request->filled('tipo')) {
            $query->where('tipo', 'like', '%' . $request->tipo . '%');
        }

        // Descripción
        if ($request->filled('descripcion')) {
            $query->where('descripcion', 'like', '%' . $request->descripcion . '%');
        }

        // Prioridad exacta (baja, media, alta)
        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        // Estado exacto (pendiente, completado, cancelado…)
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Origen / Destino (texto libre, ej. “Máquina A-1”)
        if ($request->filled('origen')) {
            $query->where('origen', 'like', '%' . $request->origen . '%');
        }
        if ($request->filled('destino')) {
            $query->whereHas('maquinaDestino', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->destino . '%');
            });
        }


        /* ─────────────── Filtros por relaciones ─────────────── */

        // Producto (códigos parciales separados por comas)
        if ($request->filled('producto_codigo')) {
            $codigos = array_filter(array_map('trim', explode(',', $request->producto_codigo)));

            $query->whereHas('producto', function ($q) use ($codigos) {
                $q->where(function ($sub) use ($codigos) {
                    foreach ($codigos as $codigo) {
                        $sub->orWhere('codigo', 'like', '%' . $codigo . '%');
                    }
                });
            });
        }

        // Filtro por ID de producto o paquete concretos (si llegan)
        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }
        if ($request->filled('paquete_id')) {
            $query->where('paquete_id', $request->paquete_id);
        }

        // Campo “producto_paquete” (permite buscar código en cualquiera de los dos)
        if ($request->filled('producto_paquete')) {
            $codigo = $request->producto_paquete;
            $query->where(function ($q) use ($codigo) {
                $q->whereHas('producto', fn($p) => $p->where('codigo', 'like', "%$codigo%"))
                    ->orWhereHas('paquete',  fn($p) => $p->where('codigo', 'like', "%$codigo%"));
            });
        }

        // Usuario que solicitó
        if ($request->filled('solicitado_por')) {
            $nombre = $request->solicitado_por;
            $query->whereHas('solicitadoPor', function ($q) use ($nombre) {
                $q->where(DB::raw("CONCAT(name, ' ', primer_apellido, ' ', segundo_apellido)"), 'like', "%$nombre%");
            });
        }

        // Usuario que ejecutó
        if ($request->filled('ejecutado_por')) {
            $nombre = $request->ejecutado_por;
            $query->whereHas('ejecutadoPor', function ($q) use ($nombre) {
                $q->where(DB::raw("CONCAT(name, ' ', primer_apellido, ' ', segundo_apellido)"), 'like', "%$nombre%");
            });
        }

        /* ─────────────── Filtros por fechas ─────────────── */

        // Rangos al estilo planillas
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_finalizacion')) {
            $query->whereDate('created_at', '<=', $request->fecha_finalizacion);
        }

        // Fechas individuales específicas
        if ($request->filled('fecha_solicitud')) {
            $query->whereDate('created_at', $request->fecha_solicitud);
        }
        if ($request->filled('fecha_ejecucion')) {
            $query->whereDate('fecha_ejecucion', $request->fecha_ejecucion);
        }

        return $query;
    }

    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        /* ─── 1. Campos directos ─────────────────────────── */
        if ($request->filled('id') || $request->filled('movimiento_id')) {
            $filtros[] = 'ID movimiento: <strong>' . ($request->id ?? $request->movimiento_id) . '</strong>';
        }

        if ($request->filled('tipo')) {
            $filtros[] = 'Tipo: <strong>' . ucfirst($request->tipo) . '</strong>';
        }

        if ($request->filled('descripcion')) {
            $filtros[] = 'Descripción contiene: <strong>' . $request->descripcion . '</strong>';
        }

        if ($request->filled('origen'))   $filtros[] = 'Origen: <strong>'   . $request->origen   . '</strong>';
        if ($request->filled('destino'))  $filtros[] = 'Destino: <strong>'  . $request->destino  . '</strong>';

        /* ─── 2. Prioridad ───────────────────────────────── */
        if ($request->filled('prioridad')) {
            $prioridades = [
                1         => 'Normal',
                2         => 'Alta',
                3         => 'Urgente',
                'normal'  => 'Normal',
                'alta'    => 'Alta',
                'urgente' => 'Urgente',
            ];
            $texto = $prioridades[$request->prioridad] ?? $request->prioridad;
            $filtros[] = 'Prioridad: <strong>' . $texto . '</strong>';
        }

        /* ─── 3. Relaciones: usuario que solicita / ejecuta ───── */
        if ($request->filled('solicitado_por')) {
            $usuario = User::where(
                DB::raw("CONCAT(name, ' ', primer_apellido, ' ', segundo_apellido)"),
                'like',
                '%' . $request->solicitado_por . '%'
            )->first();
            $filtros[] = 'Solicitado por: <strong>' . ($usuario?->nombre_completo ?? $request->solicitado_por) . '</strong>';
        }

        if ($request->filled('ejecutado_por')) {
            $usuario = User::where(
                DB::raw("CONCAT(name, ' ', primer_apellido, ' ', segundo_apellido)"),
                'like',
                '%' . $request->ejecutado_por . '%'
            )->first();
            $filtros[] = 'Ejecutado por: <strong>' . ($usuario?->nombre_completo ?? $request->ejecutado_por) . '</strong>';
        }

        /* ─── 4. Estado ─────────────────────────────────── */
        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . ucfirst($request->estado) . '</strong>';
        }

        /* ─── 5. Producto/paquete ───────────────────────── */
        if ($request->filled('producto_codigo')) {
            $filtros[] = 'Producto(s): <strong>' . $request->producto_codigo . '</strong>';
        }
        if ($request->filled('producto_paquete')) {
            $filtros[] = 'Producto/Paquete: <strong>' . $request->producto_paquete . '</strong>';
        }

        /* ─── 6. Fechas ─────────────────────────────────── */
        if ($request->filled('fecha_solicitud'))    $filtros[] = 'Fecha solicitud: <strong>'    . $request->fecha_solicitud    . '</strong>';
        if ($request->filled('fecha_ejecucion'))    $filtros[] = 'Fecha ejecución: <strong>'    . $request->fecha_ejecucion    . '</strong>';
        if ($request->filled('fecha_inicio'))       $filtros[] = 'Desde: <strong>'              . $request->fecha_inicio       . '</strong>';
        if ($request->filled('fecha_finalizacion')) $filtros[] = 'Hasta: <strong>'              . $request->fecha_finalizacion . '</strong>';

        /* ─── 7. Orden y paginación ─────────────────────── */
        if ($request->filled('sort')) {
            $sorts = [
                'prioridad'        => 'Prioridad',
                'estado'           => 'Estado',
                'fecha_solicitud'  => 'Fecha solicitud',
                'fecha_ejecucion'  => 'Fecha ejecución',
                'id'               => 'ID',
            ];
            $orden = $request->order == 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . ($sorts[$request->sort] ?? $request->sort)
                . "</strong> en orden <strong>$orden</strong>";
        }

        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . $request->per_page . '</strong> registros por página';
        }

        return $filtros;
    }

    private function getOrdenamiento(string $columna, string $titulo): string
    {
        $currentSort  = request('sort');
        $currentOrder = request('order');
        $isSorted     = $currentSort === $columna;
        $nextOrder    = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = $isSorted
            ? ($currentOrder === 'asc' ? '▲' : '▼')
            : '⇅';

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="inline-flex items-center space-x-1">' .
            '<span>' . $titulo . '</span><span class="text-xs">' . $icon . '</span></a>';
    }

    private function aplicarOrdenamiento($query, Request $request)
    {
        // → Columnas que SÍ se pueden ordenar
        $columnasPermitidas = [
            'id',
            'tipo',
            'descripcion',
            'prioridad',
            'estado',
            'fecha_solicitud',
            'fecha_ejecucion',
            'producto_id',
            'created_at',   // por si la quieres exponer
        ];

        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');

        // Sanitiza: si la columna no es válida, cae al fallback
        if (!in_array($sort, $columnasPermitidas, true)) {
            $sort = 'created_at';
        }

        // Asegura que el orden sea solo asc|desc
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $order);
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

        // Filtros
        $query = $this->aplicarFiltros($query, $request);

        // Ordenamiento (nuevo método modular)
        $query = $this->aplicarOrdenamiento($query, $request);

        // Paginación
        $perPage = $request->input('per_page', 10);
        $registrosMovimientos = $query->paginate($perPage)->appends($request->except('page'));

        $ordenables = [
            'id'              => $this->getOrdenamiento('id', 'ID'),
            'producto_id'              => $this->getOrdenamiento('producto_id', 'Producto Solicitado'),
            'tipo'            => $this->getOrdenamiento('tipo', 'Tipo'),
            'descripcion'       => $this->getOrdenamiento('descripcion', 'Descripción'),
            'prioridad'       => $this->getOrdenamiento('prioridad', 'Prioridad'),
            'solicitado_por'       => $this->getOrdenamiento('solicitado_por', 'Solicitado por'),
            'ejecutado_por'       => $this->getOrdenamiento('ejecutado_por', 'Ejecutado por'),
            'estado'       => $this->getOrdenamiento('estado', 'Estado'),
            'fecha_solicitud' => $this->getOrdenamiento('fecha_solicitud', 'Fecha Solicitud'),
            'fecha_ejecucion' => $this->getOrdenamiento('fecha_ejecucion', 'Fecha Ejecución'),
        ];
        // 🔟 Obtener texto de filtros aplicados para mostrar en la vista
        $filtrosActivos = $this->filtrosActivos($request);
        // Retornar vista con los datos paginados
        return view('movimientos.index', compact('registrosMovimientos', 'ordenables', 'filtrosActivos'));
    }

    //------------------------------------------------ CREATE() --------------------------------------------------------

    public function create(Request $request)
    {
        $productos = Producto::with('ubicacion')->get();
        $paquetes = Paquete::with('ubicacion')->get();
        $ubicaciones = Ubicacion::all();
        $maquinas = Maquina::all();
        $localizaciones = Localizacion::all();

        $codigoMateriaPrima = $request->query('codigo');
        return view('movimientos.create', compact('productos', 'paquetes', 'ubicaciones', 'maquinas', 'localizaciones', 'codigoMateriaPrima'));
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

                        $yaExiste = Movimiento::where('tipo', 'Recarga materia prima')
                            ->where('producto_base_id', $productoBase->id)
                            ->where('maquina_destino', $request->maquina_id)
                            ->where('estado', 'pendiente')
                            ->exists();

                        if ($yaExiste) {
                            throw new \Exception("Ya existe una solicitud pendiente..");
                        }

                        Movimiento::create([
                            'tipo'              => 'Recarga materia prima',
                            'maquina_origen'    => null,
                            'maquina_destino'   => $request->maquina_id,
                            'producto_id'       => null, // puede ser null
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

            return redirect()->back()->with('error', 'No se ha podido crear el movimiento.');
        }
    }

    //------------------------------------------------ STORE() --------------------------------------------------------
    public function store(Request $request)
    {
        $tipoMovimiento = $request->tipo;

        // ------------------ 1) Validación rápida  ------------------
        $validated = $request->validate([
            'codigo_general'    => 'required|string|max:50',
            'ubicacion_destino' => 'nullable|exists:ubicaciones,id',
            'maquina_destino'   => 'nullable|exists:maquinas,id',
        ], [
            'codigo_general.required' => 'Debes escanear un código.',
            'ubicacion_destino.exists' => 'Ubicación no válida.',
            'maquina_destino.exists'   => 'Máquina no válida.',
        ]);

        // ------------------ 2) Variables base  ------------------
        $codigo      = strtoupper($validated['codigo_general']);
        $maquinaId   = $validated['maquina_destino'] ?? null;
        $ubicacionId = $validated['ubicacion_destino'] ?? null;
        $esRecarga   = $maquinaId !== null;

        // ↓↓↓  Detectar máquina / ubicación ↓↓↓
        $maquinaDetectada = $esRecarga ? Maquina::find($maquinaId) : null;
        $ubicacion        = $esRecarga ? null : Ubicacion::find($ubicacionId);

        if (!$maquinaDetectada && $ubicacion) {
            $maquinaDetectada = Maquina::where('codigo', $ubicacion->descripcion)->first();
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
                                'maquina_destino'    => $maquinaDetectada->id,
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

                        // if ($productoAnterior) {
                        //     $productoAnterior->update([
                        //         'maquina_id' => null,
                        //         'estado' => 'consumido',
                        //     ]);
                        // }
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
            /* ---------- ÉXITO ---------- */
            $msg = 'Movimiento registrado correctamente.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                ]);
            }

            // flujo clásico (redirect + flashes → los recoge tu <x-alerts>)
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            Log::error('Error al registrar movimiento: ' . $e->getMessage());

            // Mensaje de error personalizado si existe
            $mensajeError = $e->getMessage() ?: 'Hubo un problema al registrar el movimiento.';

            // JSON (por ejemplo, para peticiones AJAX)
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $mensajeError, // ✅ prioriza el mensaje real del error
                    'error'   => app()->environment('local') ? $e->getMessage() : null,
                ], 500);
            }

            // Petición normal (para redirección con SweetAlert en blade)
            return back()
                ->withInput()
                ->with('error', $mensajeError);
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
