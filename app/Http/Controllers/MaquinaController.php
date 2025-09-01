<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
use App\Models\Salida;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\Producto;
use App\Models\Obra;
use App\Models\Cliente;
use App\Models\ProductoBase;
use App\Models\Pedido;
use App\Models\OrdenPlanilla;
use App\Models\AsignacionTurno;
use App\Models\User;
use App\Models\Ubicacion;
use App\Models\Movimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage; // ✅ Añadir esta línea
use Illuminate\Support\Str;

class MaquinaController extends Controller
{
    public function index(Request $request)
    {
        $usuario = auth()->user();

        /* ───────────────────────────────────────────
     * 1️⃣  RUTA OPERARIO (igual que la tuya)
     * ─────────────────────────────────────────── */
        if ($usuario->rol === 'operario') {
            $hoy = Carbon::today();
            $maniana = Carbon::tomorrow();

            $asignacion = AsignacionTurno::where('user_id', $usuario->id)
                ->whereDate('fecha', $hoy)
                ->whereNotNull('maquina_id')
                ->whereNotNull('turno_id')
                ->first();

            if (!$asignacion) {
                // 👉 No encontró turno para hoy, probamos para mañana
                $asignacion = AsignacionTurno::where('user_id', $usuario->id)
                    ->whereDate('fecha', $maniana)
                    ->whereNotNull('maquina_id')
                    ->whereNotNull('turno_id')
                    ->first();
            }


            if (!$asignacion) {
                abort(403, 'No has fichado entrada');
            }

            $maquinaId = $asignacion->maquina_id;
            $turnoId   = $asignacion->turno_id;

            // Buscar compañero
            $compañero = AsignacionTurno::where('maquina_id', $maquinaId)
                ->where('turno_id', $turnoId)
                ->where('user_id', '!=', $usuario->id)
                ->latest()
                ->first();

            session(['compañero_id' => optional($compañero)->user_id]);

            return redirect()->route('maquinas.show', ['maquina' => $maquinaId]);
        }

        /* ───────────────────────────────────────────
     * 2️⃣  RESTO DE USUARIOS
     * ─────────────────────────────────────────── */

        // ▸ 2.1 Consulta de máquinas + conteos
        $query = Maquina::with('productos')
            ->selectRaw('maquinas.*, (
            SELECT COUNT(*) FROM elementos
            WHERE elementos.maquina_id_2 = maquinas.id
        ) as elementos_ensambladora')
            ->withCount([
                'elementos as elementos_count' => fn($q) =>
                $q->where('estado', '!=', 'fabricado')
            ]);

        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%' . $request->input('nombre') . '%');
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $order  = $request->input('order', 'desc');
        if (Schema::hasColumn('maquinas', $sortBy)) {
            $query->orderBy($sortBy, $order);
        }

        $perPage = $request->input('per_page', 20);
        $registrosMaquina = $query->paginate($perPage)
            ->appends($request->except('page'));

        // ▸ 2.2 Operarios asignados hoy
        $hoy = Carbon::today();
        $usuariosPorMaquina = AsignacionTurno::with(['user', 'turno'])
            ->whereDate('fecha', $hoy)
            ->whereNotNull('maquina_id')
            ->get()
            ->groupBy('maquina_id');
        $obras = Obra::whereHas('cliente', function ($query) {
            $query->where('empresa', 'like', '%Hierros Paco Reyes%');
        })
            ->orderBy('obra')
            ->get();
        // ▸ 2.3 Render vista
        return view('maquinas.index', compact(
            'registrosMaquina',
            'usuariosPorMaquina',
            'obras'
        ));
    }

    public function showJson($id)
    {
        $maquina = Maquina::findOrFail($id);
        return response()->json($maquina);
    }

    //------------------------------------------------------------------------------------ SHOW

    public function show($id)
    {
        // 0) Cargar la máquina y activar tareas auxiliares
        $maquina = Maquina::with([
            'elementos.planilla',
            'elementos.etiquetaRelacion',
            'elementos.subetiquetas',
            'elementos.maquina',
            'elementos.maquina_2',
            'elementos.maquina_3',
            'productos'
        ])->findOrFail($id);

        $this->activarMovimientosSalidasHoy();

        // 1) Contexto base común (ubicación, usuarios, combos, etc.)
        $base = $this->cargarContextoBase($maquina);

        // 2) Branch por tipo de máquina
        if ($this->esGrua($maquina)) {
            // ------- MÁQUINA TIPO GRÚA -------
            $grua = $this->cargarContextoGrua($maquina);

            // variables neutras para la vista
            $elementosMaquina          = collect();
            $pesosElementos            = [];
            $etiquetasData             = collect();
            $elementosReempaquetados   = session('elementos_reempaquetados', []);
            $elementosAgrupados        = collect();
            $elementosAgrupadosScript  = collect();

            return view('maquinas.show', array_merge($base, $grua, compact(
                'maquina',
                'elementosMaquina',
                'pesosElementos',
                'etiquetasData',
                'elementosReempaquetados',
                'elementosAgrupados',
                'elementosAgrupadosScript',
            )));
        }

        // ------- MÁQUINA NORMAL (PRIMERA) O SEGUNDA -------
        if ($this->esSegundaMaquina($maquina)) {
            // SEGUNDA: elementos con maquina_id_2 = esta máquina
            $elementosMaquina = Elemento::with(['planilla', 'etiquetaRelacion', 'subetiquetas', 'maquina', 'maquina_2'])
                ->where('maquina_id_2', $maquina->id)
                ->get();
        } else {
            // PRIMERA: elementos con maquina_id = esta máquina
            // (si tienes relation $maquina->elementos ya lo filtra; lo dejo explícito para claridad)
            $elementosMaquina = Elemento::with(['planilla', 'etiquetaRelacion', 'subetiquetas', 'maquina'])
                ->where('maquina_id', $maquina->id)
                ->get();
        }

        // 3) Seleccionar planilla activa según orden manual (OrdenPlanilla)
        [$planillaActiva, $elementosMaquina] = $this->aplicarColaPlanillas($maquina, $elementosMaquina);

        // 4) Empaquetados visuales + datasets que usas en canvas/tabla
        $pesosElementos  = $elementosMaquina->map(fn($e) => ['id' => $e->id, 'peso' => $e->peso])->values()->toArray();

        $etiquetasData = $elementosMaquina
            ->filter(fn($e) => !empty($e->etiqueta_sub_id))
            ->groupBy('etiqueta_sub_id')
            ->sortBy(function ($grupo, $subId) {
                if (preg_match('/^(.*?)[\.\-](\d+)$/', $subId, $m)) {
                    return sprintf('%s-%010d', $m[1], (int) $m[2]);
                }
                return $subId . '-0000000000';
            })
            ->map(fn($grupo, $subId) => [
                'codigo'     => (string) $subId,
                'elementos'  => $grupo->pluck('id')->toArray(),
                'pesoTotal'  => $grupo->sum('peso'),
            ])
            ->values();

        $elementosReempaquetados = session('elementos_reempaquetados', []);
        $elementosAgrupados = $elementosMaquina
            ->groupBy('etiqueta_sub_id')
            ->sortBy(function ($grupo, $subId) {
                if (preg_match('/^(.*?)[\.\-](\d+)$/', $subId, $m)) {
                    return sprintf('%s-%010d', $m[1], (int) $m[2]);
                }
                return $subId . '-0000000000';
            });

        $elementosAgrupadosScript = $elementosAgrupados->map(fn($grupo) => [
            'etiqueta'   => $grupo->first()->etiquetaRelacion,
            'planilla'   => $grupo->first()->planilla,
            'elementos'  => $grupo->map(fn($e) => [
                'id'         => $e->id,
                'codigo'     => $e->codigo,
                'dimensiones' => $e->dimensiones,
                'estado'     => $e->estado,
                'peso'       => $e->peso_kg,
                'diametro'   => $e->diametro_mm,
                'longitud'   => $e->longitud_cm,
                'barras'     => $e->barras,
                'figura'     => $e->figura,
            ])->values(),
        ])->values();

        // 5) Turno hoy del usuario
        $turnoHoy = AsignacionTurno::where('user_id', auth()->id())
            ->whereDate('fecha', now())
            ->with('maquina')
            ->first();

        // 6) variables “grúa” vacías para mantener la vista estable
        $movimientosPendientes = collect();
        $movimientosCompletados = collect();
        $ubicacionesDisponiblesPorProductoBase = [];
        $pedidosActivos = collect();

        $productoBaseSolicitados = Movimiento::where('tipo', 'recarga materia prima')
            ->where('estado', 'pendiente')
            ->where('maquina_destino', $maquina->id)
            ->pluck('producto_base_id')
            ->unique() ?? collect(); // 🔁 nunca null


        return view('maquinas.show', array_merge($base, compact(
            'maquina',
            'elementosMaquina',
            'pesosElementos',
            'etiquetasData',
            'elementosReempaquetados',
            'movimientosPendientes',
            'movimientosCompletados',
            'ubicacionesDisponiblesPorProductoBase',
            'pedidosActivos',
            'elementosAgrupados',
            'elementosAgrupadosScript',
            'turnoHoy',
            'productoBaseSolicitados'
        )));
    }

    /* =========================
   HELPERS PRIVADOS
   ========================= */
    public static function productosSolicitadosParaMaquina($maquinaId)
    {
        return Movimiento::where('tipo', 'recarga_materia_prima')
            ->where('estado', 'pendiente')
            ->where('maquina_id', $maquinaId)
            ->pluck('producto_base_id')
            ->unique()
            ->toArray();
    }

    private function esGrua(Maquina $m): bool
    {
        return stripos((string)$m->tipo, 'grua') !== false || stripos((string)$m->nombre, 'grua') !== false;
    }

    // Si tienes un campo explícito para “segunda” úsalo aquí.
    // Por defecto asumo “segunda” = máquinas que trabajan como post-proceso, p.ej. ensambladora.
    private function esSegundaMaquina(Maquina $m): bool
    {
        $tipo = strtolower((string)$m->tipo);

        return str_contains($tipo, 'ensambladora')
            || str_contains($tipo, 'dobladora manual')   // 👈 añade esto
            || (property_exists($m, 'orden') && (int)$m->orden === 2);
    }


    private function cargarContextoBase(Maquina $maquina): array
    {
        $ubicacion = Ubicacion::where('descripcion', 'like', "%{$maquina->codigo}%")->first();
        $maquinas  = Maquina::orderBy('nombre')->get();

        $productosBaseCompatibles = ProductoBase::where('tipo', $maquina->tipo_material)
            ->whereBetween('diametro', [$maquina->diametro_min, $maquina->diametro_max])
            ->orderBy('diametro')
            ->get();

        $usuario1 = auth()->user();
        $usuario1->name = html_entity_decode($usuario1->name, ENT_QUOTES, 'UTF-8');

        $usuario2 = null;
        if (Session::has('compañero_id')) {
            $usuario2 = User::find(Session::get('compañero_id'));
            if ($usuario2) $usuario2->name = html_entity_decode($usuario2->name, ENT_QUOTES, 'UTF-8');
        }

        // ✅ turnoHoy común a todos los flujos (incluida grúa)
        $turnoHoy = AsignacionTurno::where('user_id', auth()->id())
            ->whereDate('fecha', now())
            ->with('maquina')
            ->first();

        return compact('ubicacion', 'maquinas', 'productosBaseCompatibles', 'usuario1', 'usuario2', 'turnoHoy');
    }


    private function cargarContextoGrua(Maquina $maquina): array
    {
        $ubicacionesDisponiblesPorProductoBase = [];

        $movimientosPendientes = Movimiento::with(['solicitadoPor', 'producto.ubicacion', 'pedido.fabricante', 'productoBase'])
            ->where('estado', 'pendiente')
            ->orderBy('prioridad', 'asc')
            ->get();

        $movimientosCompletados = Movimiento::with(['solicitadoPor', 'ejecutadoPor', 'producto.ubicacion', 'productoBase'])
            ->where('estado', 'completado')
            ->where('ejecutado_por', auth()->id())
            ->orderBy('updated_at', 'desc')
            ->take(20)
            ->get();

        $pedidosActivos = Pedido::where('estado', 'activo')->orderBy('updated_at', 'desc')->get();

        foreach ($movimientosPendientes as $mov) {
            if ($mov->producto_base_id) {
                $productosCompatibles = Producto::with('ubicacion')
                    ->where('producto_base_id', $mov->producto_base_id)
                    ->where('estado', 'almacenado')
                    ->get();

                $ubicaciones = $productosCompatibles->filter(fn($p) => $p->ubicacion)
                    ->map(fn($p) => [
                        'id'         => $p->ubicacion->id,
                        'nombre'     => $p->ubicacion->nombre,
                        'producto_id' => $p->id,
                        'codigo'     => $p->codigo,
                    ])->unique('id')->values()->toArray();

                $ubicacionesDisponiblesPorProductoBase[$mov->producto_base_id] = $ubicaciones;
            }
        }

        return compact('movimientosPendientes', 'movimientosCompletados', 'ubicacionesDisponiblesPorProductoBase', 'pedidosActivos');
    }

    /**
     * Devuelve [planillaActiva, elementosFiltradosAPlanillaActiva]
     * según el orden manual (OrdenPlanilla) de esta máquina.
     */
    private function aplicarColaPlanillas(Maquina $maquina, \Illuminate\Support\Collection $elementos)
    {
        // Agrupar por planilla
        $elementosPorPlanilla = $elementos->groupBy('planilla_id');

        // Orden manual
        $ordenManual = OrdenPlanilla::where('maquina_id', $maquina->id)
            ->get()
            ->pluck('posicion', 'planilla_id'); // [planilla_id => posicion]

        $elementosPorPlanilla = $elementosPorPlanilla->sortBy(function ($grupo, $planillaId) use ($ordenManual) {
            return $ordenManual[$planillaId] ?? PHP_INT_MAX;
        });

        $planillaActiva = null;
        foreach ($elementosPorPlanilla as $grupo) {
            $planilla = $grupo->first()->planilla;
            if (!$planilla || !$ordenManual->has($planilla->id)) {
                continue;
            }
            $planillaActiva = $planilla;
            break;
        }

        $elementosFiltrados = $planillaActiva
            ? $elementos->where('planilla_id', $planillaActiva->id)->values()
            : collect();

        return [$planillaActiva, $elementosFiltrados];
    }

    private function activarMovimientosSalidasHoy(): void
    {
        // 👉 Fecha actual (sin hora)
        $hoy = Carbon::today();

        // 🔎 Buscar todas las salidas programadas para hoy
        $salidasHoy = Salida::whereDate('fecha_salida', $hoy)->get();

        foreach ($salidasHoy as $salida) {
            // 🔎 Comprobar si ya existe un movimiento asociado a esta salida
            $existeMovimiento = Movimiento::where('salida_id', $salida->id)
                ->where('tipo', 'salida')
                ->exists();

            if (!$existeMovimiento) {

                // 👉 Datos básicos
                $camion = optional($salida->camion)->modelo ?? 'Sin modelo';
                $empresaTransporte = optional($salida->empresaTransporte)->nombre ?? 'Sin empresa';
                $horaSalida = \Carbon\Carbon::parse($salida->fecha_salida)->format('H:i');
                $codigoSalida = $salida->codigo_salida;
                // 👉 Armar listado de obras y clientes relacionados
                $obrasClientes = $salida->salidaClientes->map(function ($sc) {
                    $obra = optional($sc->obra)->obra ?? 'Sin obra';
                    $cliente = optional($sc->cliente)->empresa ?? 'Sin cliente';
                    return "$obra - $cliente";
                })->filter()->implode(', ');

                // 👉 Construir la descripción final (sin usar optional de nuevo)
                $descripcion = "$codigoSalida. Se solicita carga del camión ($camion) - ($empresaTransporte) para [$obrasClientes], tiene que estar listo a las $horaSalida";


                // ⚡ Crear movimiento nuevo
                Movimiento::create([
                    'tipo' => 'salida',
                    'salida_id' => $salida->id,
                    'estado' => 'pendiente',
                    'fecha_solicitud' => now(),
                    'solicitado_por' => null,
                    'prioridad' => 2,
                    'descripcion' => $descripcion,
                    // 👉 Rellena otros campos si lo necesitas, por ejemplo prioridad o descripción
                ]);
            }
        }
    }
    public function create()
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('maquinas.index')->with('abort', 'No tienes los permisos necesarios.');
        }

        $clienteId = Cliente::where('empresa', 'Hierros Paco Reyes')->value('id');

        $obras = Obra::where('cliente_id', $clienteId)
            ->orderBy('obra')
            ->get();

        return view('maquinas.create', compact('obras'));
    }

    // Método para guardar la ubicación en la base de datos
    public function store(Request $request)
    {
        DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
        try {
            // Validación de los datos del formulario
            $request->validate([
                'codigo'       => 'required|string|max:6|unique:maquinas,codigo',
                'nombre'       => 'required|string|max:40|unique:maquinas,nombre',
                'tipo'         => 'nullable|string|max:50|in:cortadora_dobladora,ensambladora,soldadora,cortadora manual,dobladora manual',
                'obra_id'      => 'nullable|exists:obras,id',
                'diametro_min' => 'nullable|integer',
                'diametro_max' => 'nullable|integer',
                'peso_min'     => 'nullable|integer',
                'peso_max'     => 'nullable|integer',
            ], [
                // Mensajes personalizados
                'codigo.required' => 'El campo "código" es obligatorio.',
                'codigo.string'   => 'El campo "código" debe ser una cadena de texto.',
                'codigo.max'      => 'El campo "código" no puede tener más de 6 caracteres.',
                'codigo.unique'   => 'Ya existe una máquina con el mismo código.',

                'nombre.required' => 'El campo "nombre" es obligatorio.',
                'nombre.string'   => 'El campo "nombre" debe ser una cadena de texto.',
                'nombre.max'      => 'El campo "nombre" no puede tener más de 40 caracteres.',
                'nombre.unique'   => 'Ya existe una máquina con el mismo nombre.',

                'tipo.string'     => 'El campo "tipo" debe ser una cadena de texto.',
                'tipo.max'        => 'El campo "tipo" no puede tener más de 50 caracteres.',
                'tipo.in'         => 'El tipo no está entre los posibles.',

                'diametro_min.integer' => 'El campo "diámetro mínimo" debe ser un número entero.',
                'diametro_max.integer' => 'El campo "diámetro máximo" debe ser un número entero.',
                'peso_min.integer'     => 'El campo "peso mínimo" debe ser un número entero.',
                'peso_max.integer'     => 'El campo "peso máximo" debe ser un número entero.',
                'obra_id.exists'       => 'La obra seleccionada no es válida.',
            ]);

            // Crear la nueva máquina en la base de datos
            Maquina::create([
                'codigo'       => $request->codigo,
                'nombre'       => $request->nombre,
                'tipo'         => $request->tipo,
                'obra_id'      => $request->obra_id,
                'diametro_min' => $request->diametro_min,
                'diametro_max' => $request->diametro_max,
                'peso_min'     => $request->peso_min,
                'peso_max'     => $request->peso_max,
            ]);

            DB::commit();  // Confirmamos la transacción

            return redirect()->route('maquinas.index')->with('success', 'Máquina creada con éxito.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();  // Revertimos la transacción si hay error de validación
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();  // Revertimos la transacción si hay error general
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }

    public function guardarSesion(Request $request)
    {
        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',
            'users_id_2' => 'nullable|exists:users,id' // Ahora puede ser null
        ]);

        // Guardar el nuevo compañero en la sesión (o eliminar si es null)
        session(['compañero_id' => $request->users_id_2]);

        return response()->json(['success' => true]);
    }

    // TurnoController.php
    public function cambiarMaquina(Request $request)
    {
        $request->validate([
            'asignacion_id' => 'required|exists:asignaciones_turnos,id',
            'nueva_maquina_id' => 'required|exists:maquinas,id',
        ]);

        $asignacion = AsignacionTurno::findOrFail($request->asignacion_id);
        $asignacion->maquina_id = $request->nueva_maquina_id;
        $asignacion->save();

        return redirect()
            ->route('maquinas.index')
            ->with('success', 'Máquina actualizada correctamente.');
    }

    public function cambiarEstado(Request $request, $id)
    {
        // Validar el estado recibido (puede ser nulo o string corto)
        $request->validate([
            'estado' => 'nullable|string|max:50',
        ]);

        // Buscar la máquina y actualizar estado
        $maquina = Maquina::findOrFail($id);
        $maquina->estado = $request->input('estado', 'activa');
        $maquina->save();

        // 🧠 Detectar si se espera una respuesta JSON (Ajax, fetch, etc.)
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'estado' => $maquina->estado,
                'mensaje' => 'Estado actualizado correctamente.'
            ]);
        }

        // 🌐 Si no se espera JSON, redirigir normalmente
        return redirect()->back()->with('success', 'Estado de la máquina actualizado correctamente.');
    }
    public function edit($id)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('maquinas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        // Buscar la máquina por su ID
        $maquina = Maquina::findOrFail($id);

        // Retornar la vista con los datos de la máquina
        return view('maquinas.edit', compact('maquina'));
    }

    public function update(Request $request, $id)
    {
        // Validar los datos del formulario
        $validatedData = $request->validate([
            'codigo'       => 'required|string|max:6|unique:maquinas,codigo,' . $id,
            'nombre'       => 'required|string|max:40',
            'obra_id'      => 'nullable|exists:obras,id', // 👈 añadimos validación para la obra
            'diametro_min' => 'nullable|integer',
            'diametro_max' => 'nullable|integer',
            'peso_min'     => 'nullable|integer',
            'peso_max'     => 'nullable|integer',
            'estado'       => 'nullable|string|in:activa,en mantenimiento,inactiva',
        ], [
            'codigo.required'   => 'El campo "código" es obligatorio.',
            'codigo.string'     => 'El campo "código" debe ser una cadena de texto.',
            'codigo.max'        => 'El campo "código" no puede tener más de 6 caracteres.',
            'codigo.unique'     => 'El código ya existe, por favor ingrese otro diferente.',

            'nombre.required'   => 'El campo "nombre" es obligatorio.',
            'nombre.string'     => 'El campo "nombre" debe ser una cadena de texto.',
            'nombre.max'        => 'El campo "nombre" no puede tener más de 40 caracteres.',

            'obra_id.exists'    => 'La obra seleccionada no es válida.', // 👈 mensaje personalizado

            'diametro_min.integer' => 'El "diámetro mínimo" debe ser un número entero.',
            'diametro_max.integer' => 'El "diámetro máximo" debe ser un número entero.',
            'peso_min.integer'     => 'El "peso mínimo" debe ser un número entero.',
            'peso_max.integer'     => 'El "peso máximo" debe ser un número entero.',

            'estado.in' => 'El estado debe ser: activa, en mantenimiento o inactiva.',
        ]);

        // Iniciar la transacción
        DB::beginTransaction();

        try {
            // Buscar la máquina por su ID
            $maquina = Maquina::findOrFail($id);

            // Actualizar los datos de la máquina
            $maquina->update($validatedData);

            // Confirmar la transacción
            DB::commit();

            // Redirigir con un mensaje de éxito
            return redirect()
                ->route('maquinas.index')
                ->with('success', 'La máquina se actualizó correctamente.');
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();

            // Redirigir con un mensaje de error
            return redirect()
                ->back()
                ->with('error', 'Hubo un problema al actualizar la máquina. Intenta nuevamente. Error: ' . $e->getMessage());
        }
    }


    public function actualizarImagen(Request $request, Maquina $maquina)
    {
        $request->validate([
            'imagen' => 'required|image|max:2048',
        ]);

        $nombreOriginal = $request->file('imagen')->getClientOriginalName();
        $nombreLimpio   = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME));
        $extension      = $request->file('imagen')->getClientOriginalExtension();
        $nombreFinal    = $nombreLimpio . '.' . $extension;
        $directorio = public_path('maquinasImagenes');
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }

        // ✅ Guardamos directamente en public/maquinasImagenes (evita conflicto con /maquinas)
        $request->file('imagen')->move(public_path('maquinasImagenes'), $nombreFinal);

        $maquina->imagen = 'maquinasImagenes/' . $nombreFinal;
        $maquina->save();

        return back()->with('success', 'Imagen actualizada correctamente.');
    }


    public function destroy($id)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('maquinas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        DB::beginTransaction();
        try {
            // Buscar la maquina a eliminar
            $maquina = Maquina::findOrFail($id);

            // Eliminar la entrada
            $maquina->delete();

            DB::commit();  // Confirmamos la transacción
            return redirect()->route('maquinas.index')->with('success', 'Máquina eliminada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error al eliminar la entrada: ' . $e->getMessage());
        }
    }
}
