<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
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

            $asignacion = AsignacionTurno::where('user_id', $usuario->id)
                ->whereDate('fecha', $hoy)
                ->whereNotNull('maquina_id')
                ->whereNotNull('turno_id')
                ->first();

            if (!$asignacion) {
                abort(403, 'No tienes ningún turno hoy.');
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
        // ---------------------------------------------------------------
        // 1) Cargar la máquina con relaciones necesarias precargadas
        // ---------------------------------------------------------------
        $maquina = Maquina::with([
            'elementos.planilla',
            'elementos.etiquetaRelacion',
            'elementos.subetiquetas',
            'elementos.maquina',
            'elementos.maquina_2',
            'elementos.maquina_3',
            'productos'
        ])->findOrFail($id);

        // ---------------------------------------------------------------
        // 2) Buscar la ubicación vinculada al código de la máquina
        // ---------------------------------------------------------------
        $ubicacion = Ubicacion::where('descripcion', 'like', "%{$maquina->codigo}%")->first();
        $maquinas = Maquina::orderBy('nombre')->get();

        // ---------------------------------------------------------------
        // 3) Obtener productos base compatibles según tipo y diámetro
        // ---------------------------------------------------------------
        $productosBaseCompatibles = ProductoBase::where('tipo', $maquina->tipo_material)
            ->whereBetween('diametro', [$maquina->diametro_min, $maquina->diametro_max])
            ->orderBy('diametro')
            ->get();

        // ---------------------------------------------------------------
        // 4) Obtener usuario autenticado y compañero de sesión (si existe)
        // ---------------------------------------------------------------
        $usuario1 = auth()->user();
        $usuario1->name = html_entity_decode($usuario1->name, ENT_QUOTES, 'UTF-8');

        $usuario2 = null;
        if (Session::has('compañero_id')) {
            $usuario2 = User::find(Session::get('compañero_id'));
            if ($usuario2) {
                $usuario2->name = html_entity_decode($usuario2->name, ENT_QUOTES, 'UTF-8');
            }
        }

        // ---------------------------------------------------------------
        // 5) Obtener todos los elementos asignados a la máquina
        // ---------------------------------------------------------------
        $elementosMaquina = $maquina->elementos()->with('subetiquetas')->get();

        // ---------------------------------------------------------------
        // 6) Si es grúa, obtener movimientos asociados
        // ---------------------------------------------------------------
        if (stripos($maquina->tipo, 'grua') !== false) {
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
                            'id' => $p->ubicacion->id,
                            'nombre' => $p->ubicacion->nombre,
                            'producto_id' => $p->id,
                            'codigo' => $p->codigo,
                        ])->unique('id')->values()->toArray();

                    $ubicacionesDisponiblesPorProductoBase[$mov->producto_base_id] = $ubicaciones;
                }
            }
        } else {
            $movimientosPendientes = collect();
            $movimientosCompletados = collect();
            $ubicacionesDisponiblesPorProductoBase = [];
            $pedidosActivos = collect();
        }

        // ---------------------------------------------------------------
        // 7) Seleccionar la primera planilla activa con elementos pendientes
        // ---------------------------------------------------------------
        // 7️⃣ Agrupar elementos por planilla_id
        $elementosPorPlanilla = $elementosMaquina->groupBy('planilla_id');

        // 🧠 Obtener las posiciones desde la relación planillas_orden
        $ordenManual = OrdenPlanilla::where('maquina_id', $maquina->id)
            ->get()
            ->pluck('posicion', 'planilla_id'); // [planilla_id => posicion]
        // dd($ordenManual);
        // 🔁 Ordenar los grupos de elementos usando ese orden manual
        $elementosPorPlanilla = $elementosPorPlanilla->sortBy(function ($grupo, $planillaId) use ($ordenManual) {
            return $ordenManual[$planillaId] ?? PHP_INT_MAX; // Si no hay orden, lo manda al final
        });

        $planillaActiva = null;
        foreach ($elementosPorPlanilla as $grupo) {
            $planilla = $grupo->first()->planilla;

            if (
                str_contains(strtolower($maquina->tipo), 'ensambladora') &&
                str_contains(strtolower($planilla->ensamblado), 'carcasas')
            ) {
                $tieneRelacionCorrecta = $grupo->contains(
                    fn($e) =>
                    $e->maquina_id == $maquina->id || $e->maquina_id_2 == $maquina->id
                );
            }

            $planillaActiva = $planilla;
            break;
        }

        $elementosMaquina = $planillaActiva ? $elementosMaquina->where('planilla_id', $planillaActiva->id) : collect();

        // ---------------------------------------------------------------
        // 8) Si es ensambladora, incluir elementos relacionados de otras máquinas
        // ---------------------------------------------------------------
        if (
            stripos($maquina->tipo, 'ensambladora') !== false &&
            $planillaActiva &&
            str_contains(strtolower(trim($planillaActiva->ensamblado)), 'carcasas')
        ) {
            // 1. Añadir estribos que vienen de otras máquinas (maquina_id_2 = ensambladora actual)
            $elementosExtra = Elemento::where('maquina_id_2', $maquina->id)
                ->where('maquina_id', '!=', $maquina->id)
                ->get();

            $elementosMaquina = $elementosMaquina->merge($elementosExtra);

            // 2. Obtener las bases de subetiquetas de los estribos (ej: ETQ-25-001.02 → etq-25-001)
            $bases = $elementosExtra->pluck('etiqueta_sub_id')
                ->map(fn($id) => strtolower(preg_replace('/[\.\-]\d+$/', '', $id)))
                ->unique()
                ->values()
                ->all();

            // 3. Buscar elementos de diámetro 5 que coincidan con esas bases
            $elementosFinales = Elemento::where(function ($query) use ($bases) {
                foreach ($bases as $base) {
                    $query->orWhere('etiqueta_sub_id', 'like', "$base-%")
                        ->orWhere('etiqueta_sub_id', 'like', "$base.%");
                }
            })
                ->where('diametro', 5.00)
                ->whereHas('maquina', fn($q) => $q->where('tipo', 'like', '%ensambladora%'))
                ->get();

            // 4. Añadirlos si no estaban ya
            $idsExistentes = $elementosMaquina->pluck('id')->toArray();
            $elementosFinalesFiltrados = $elementosFinales->filter(fn($e) => !in_array($e->id, $idsExistentes));
            $elementosMaquina = $elementosMaquina->merge($elementosFinalesFiltrados);

            // 5. Fusión de subetiquetas: asignar a todos los de misma base la subetiqueta más significativa
            $agrupadasPorBase = $elementosMaquina->groupBy(function ($e) {
                return preg_replace('/[\.\-]\d+$/', '', $e->etiqueta_sub_id);
            });

            foreach ($agrupadasPorBase as $base => $grupo) {
                // Cargar etiquetas del grupo
                $etiquetas = Etiqueta::whereIn('id', $grupo->pluck('etiqueta_id')->unique())->get();

                // Buscar la que tiene tiempos
                $etiquetaConTiempos = $etiquetas->first(function ($etq) {
                    return $etq->fecha_inicio || $etq->fecha_finalizacion;
                });

                if (!$etiquetaConTiempos) continue;

                $subIdCorrecto = $etiquetaConTiempos->etiqueta_sub_id;
                $etiquetaIdCorrecto = $etiquetaConTiempos->id;

                foreach ($grupo as $elemento) {
                    if (
                        $elemento->etiqueta_id !== $etiquetaIdCorrecto &&
                        $elemento->etiqueta_sub_id !== $subIdCorrecto
                    ) {
                        $etiquetaAntigua = Etiqueta::find($elemento->etiqueta_id);

                        $elemento->etiqueta_id = $etiquetaIdCorrecto;
                        $elemento->etiqueta_sub_id = $subIdCorrecto;
                        $elemento->save();

                        // Si la antigua etiqueta ya no tiene elementos, eliminarla
                        if (
                            $etiquetaAntigua &&
                            !Elemento::where('etiqueta_id', $etiquetaAntigua->id)->exists()
                        ) {
                            $etiquetaAntigua->delete();
                        }
                    }
                }
            }



            // 6. 🔴 FILTRAR: solo estribos o ensambladora con base válida
            $elementosMaquina = $elementosMaquina->filter(function ($e) use ($bases) {
                if (!$e->maquina) return true;

                $tipo = strtolower($e->maquina->tipo);
                $baseElemento = strtolower(preg_replace('/[\.\-]\d+$/', '', $e->etiqueta_sub_id));

                return str_contains($tipo, 'estribadora') ||
                    ($e->diametro == 5.00 && str_contains($tipo, 'ensambladora') && in_array($baseElemento, $bases));
            });
        }



        // ---------------------------------------------------------------
        // 9) Calcular pesos por elemento y agrupar etiquetas
        // ---------------------------------------------------------------
        $pesosElementos = $elementosMaquina->map(fn($e) => ['id' => $e->id, 'peso' => $e->peso])->values()->toArray();

        $etiquetasData = $elementosMaquina->filter(fn($e) => !empty($e->etiqueta_sub_id))
            ->groupBy('etiqueta_sub_id')
            ->map(fn($grupo, $subId) => [
                'codigo' => (string) $subId,
                'elementos' => $grupo->pluck('id')->toArray(),
                'pesoTotal' => $grupo->sum('peso'),
            ])->values();

        // ---------------------------------------------------------------
        // 10) Agrupar visualmente los elementos filtrados por subetiqueta
        // ---------------------------------------------------------------
        $elementosReempaquetados = Session::get('elementos_reempaquetados', []);
        $idsReempaquetados = collect($elementosReempaquetados);

        function debeSerExcluido($e)
        {
            return $e->estado === 'fabricado' && !is_null(optional($e->etiquetaRelacion)->paquete_id);
        }


        $elementosFiltrados = $elementosMaquina->filter(function ($e) use ($maquina) {
            if (stripos($maquina->tipo, 'ensambladora') !== false) {
                return $e->maquina_id_2 == $maquina->id || $e->maquina_id == $maquina->id || $e->planilla_id == optional($e->planilla)->id;
            }
            if (stripos($maquina->nombre, 'soldadora') !== false) {
                return !debeSerExcluido($e) && $e->maquina_id_3 == $maquina->id && strtolower(optional($e->etiquetaRelacion)->estado ?? '') === 'soldando';
            }
            return !debeSerExcluido($e);
        });

        $elementosAgrupados = $elementosFiltrados->groupBy('etiqueta_sub_id');

        $elementosAgrupadosScript = $elementosAgrupados->map(fn($grupo) => [
            'etiqueta' => $grupo->first()->etiquetaRelacion,
            'planilla' => $grupo->first()->planilla,
            'elementos' => $grupo->map(fn($e) => [
                'id' => $e->id,
                'dimensiones' => $e->dimensiones,
                'estado' => $e->estado,
                'peso' => $e->peso_kg,
                'diametro' => $e->diametro_mm,
                'longitud' => $e->longitud_cm,
                'barras' => $e->barras,
                'figura' => $e->figura,
            ])->values(),
        ])->values();

        // ---------------------------------------------------------------
        // 11) Calcular el turno que tiene el usuario autenticado hoy
        // ---------------------------------------------------------------
        $turnoHoy = AsignacionTurno::where('user_id', auth()->id())
            ->whereDate('fecha', now())
            ->with('maquina') // si quieres info adicional de la máquina
            ->first();

        // ---------------------------------------------------------------
        // 11) Retornar vista con todos los datos precargados
        // ---------------------------------------------------------------
        return view('maquinas.show', compact(
            'maquina',
            'ubicacion',
            'usuario1',
            'usuario2',
            'elementosMaquina',
            'pesosElementos',
            'etiquetasData',
            'elementosReempaquetados',
            'maquinas',
            'movimientosPendientes',
            'movimientosCompletados',
            'ubicacionesDisponiblesPorProductoBase',
            'productosBaseCompatibles',
            'pedidosActivos',
            'elementosAgrupados',
            'elementosAgrupadosScript',
            'turnoHoy'
        ));
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
