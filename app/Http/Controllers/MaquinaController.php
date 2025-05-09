<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\AsignacionTurno;
use App\Models\User;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;


class MaquinaController extends Controller
{
    public function index(Request $request)
    {
        $usuario = auth()->user();

        if ($usuario->rol === 'operario') {
            $asignacion = AsignacionTurno::where('user_id', $usuario->id)
                ->whereNotNull('maquina_id')
                ->whereNotNull('turno_id')
                ->latest()
                ->first();

            if (!$asignacion) {
                abort(403, 'No tienes ningún turno hoy.');
            }

            $maquinaId = $asignacion->maquina_id;
            $turnoId   = $asignacion->turno_id;

            // Buscar compañero con misma máquina y mismo turno
            $compañero = AsignacionTurno::where('maquina_id', $maquinaId)
                ->where('turno_id', $turnoId)
                ->where('user_id', '!=', $usuario->id)
                ->latest()
                ->first();

            // Guardar en sesión como lo hacía tu método guardarSesion
            session(['compañero_id' => optional($compañero)->user_id]);

            // Redirigir directamente a la máquina
            return redirect()->route('maquinas.show', ['maquina' => $maquinaId]);
        }

        //Resto de usuarios...
        // Conseguir lista de operarios
        $usuarios = User::where('id', '!=', auth()->id())
            ->where('rol', 'operario')
            ->get();
        $elementos = Elemento::select(
            'elementos.*',
            // Alias que elige la columna de agrupamiento según el tipo de máquina
            DB::raw("
                    CASE
                        WHEN maquinas.tipo = 'ensambladora' THEN elementos.maquina_id_2
                        WHEN maquinas.tipo = 'soldadora'    THEN elementos.maquina_id_3
                        ELSE elementos.maquina_id
                    END as maquina_group_id
                ")
        )
            // Necesitamos el tipo de la máquina original
            ->join('maquinas', 'maquinas.id', '=', 'elementos.maquina_id')
            // Para ordenar por fecha de entrega de la planilla
            ->join('planillas', 'planillas.id', '=', 'elementos.planilla_id')

            // ➖ Excluimos los elementos que *tengan* al menos 1 etiqueta completada
            ->whereDoesntHave('subetiquetas', function ($q) {
                $q->where('estado', 'completada');
            })


            // Filtrado condicional:
            ->where(function ($q) {
                $q->where(function ($q1) {
                    // máquinas “normales” (ni ensambladora ni soldadora)
                    $q1->where('maquinas.tipo', '<>', 'ensambladora')
                        ->where('maquinas.tipo', '<>', 'soldadora')
                        ->whereNotNull('elementos.maquina_id');
                })
                    ->orWhere(function ($q2) {
                        // ensambladoras → uso de maquina_id_2
                        $q2->where('maquinas.tipo', 'ensambladora')
                            ->whereNotNull('elementos.maquina_id_2');
                    })
                    ->orWhere(function ($q3) {
                        // soldadoras → uso de maquina_id_3
                        $q3->where('maquinas.tipo', 'soldadora')
                            ->whereNotNull('elementos.maquina_id_3');
                    });
            })

            // Ordenamos en SQL por el alias y por fecha de entrega
            ->orderBy('maquina_group_id', 'asc')
            ->orderBy('planillas.fecha_estimada_entrega', 'asc')

            // Cargamos la relación para evitar N+1
            ->with('planilla')

            ->get();

        // Finalmente agrupamos en memoria según el alias
        $colaPorMaquina = $elementos
            ->groupBy('maquina_group_id')
            ->map->values();
        // Ahora $colaPorMaquina es una Collection donde cada clave es el ID de máquina
        // y el valor es otra Collection (ordenada por fecha) de elementos asociados.

        // Conseguir lista de maquinas con productos y conteo de elementos en cada una 
        $query = Maquina::with('productos')
            ->selectRaw('maquinas.*, (
                SELECT COUNT(*) FROM elementos
                WHERE elementos.maquina_id_2 = maquinas.id
            ) as elementos_ensambladora')
            ->withCount(['elementos as elementos_count' => function ($query) {
                $query->where('estado', '!=', 'fabricado');
            }]);

        // Aplicar filtro por nombre si se pasa como parámetro en la solicitud
        if ($request->filled('nombre')) {
            $nombre = $request->input('nombre');
            $query->where('nombre', 'like', '%' . $nombre . '%');
        }

        // Ordenar por un campo dinámico
        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');

        // Validar que el campo de ordenación existe en la base de datos para evitar inyección SQL
        if (Schema::hasColumn('maquinas', $sortBy)) {
            $query->orderBy($sortBy, $order);
        }

        // Paginación
        $perPage = $request->input('per_page', 10);
        $registrosMaquina = $query->paginate($perPage)->appends($request->except('page'));
        // DEPURACION INTERESANTE
        // $datosDepuracion = $registrosMaquina->map(function ($maquina) {
        //     return [
        //         'id' => $maquina->id,
        //         'nombre' => $maquina->nombre,
        //         'tipo' => $maquina->tipo,
        //         'elementos_count' => $maquina->elementos_count,
        //         'elementos_ensambladora' => $maquina->elementos_ensambladora,
        //     ];
        // });
        //dd($datosDepuracion->toArray());

        // Pasar las máquinas y usuarios a la vista
        return view('maquinas.index', compact('registrosMaquina', 'usuarios', 'colaPorMaquina'));
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
        // 1) Cargar la máquina con relaciones precargadas
        // ---------------------------------------------------------------
        $maquina = Maquina::with([
            'elementos.planilla',        // Planilla de cada elemento
            'elementos.etiquetaRelacion', // Etiqueta principal de cada elemento
            'elementos.subetiquetas',     // Sub-etiquetas de cada elemento
            'productos'                   // Productos actualmente en la máquina
        ])->findOrFail($id);

        // ---------------------------------------------------------------
        // 2) Determinar la ubicación buscando el código de la máquina
        // ---------------------------------------------------------------
        $ubicacion = Ubicacion::where('descripcion', 'like', "%{$maquina->codigo}%")->first();
        $maquinas = Maquina::orderBy('nombre')->get(); // O cualquier otro criterio
        // ---------------------------------------------------------------
        // 3) Obtener el usuario autenticado y compañero de sesión (si existe)
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
        // 4) Colección base de elementos para esta máquina
        //    - Solo los que están en estado "Pendiente"
        //    - Incluye sub-etiquetas
        // ---------------------------------------------------------------
        $elementosMaquina = $maquina
            ->elementos()
            // ->where('estado', 'pendiente')
            ->with('subetiquetas')
            ->get();

        // ---------------------------------------------------------------
        // 5) Si es una ensambladora, añadir elementos trasladados desde otra máquina
        // ---------------------------------------------------------------
        if (stripos($maquina->tipo, 'ensambladora') !== false) {
            $elementosExtra = Elemento::where('maquina_id_2', $maquina->id)
                ->where('maquina_id', '!=', $maquina->id)
                ->with('subetiquetas')
                ->get();

            // Unir ambas colecciones
            $elementosMaquina = $elementosMaquina->merge($elementosExtra);
        }

        // ---------------------------------------------------------------
        // 6) Agrupar elementos por planilla y ordenar por fecha de entrega
        // ---------------------------------------------------------------
        $elementosPorPlanilla = $elementosMaquina
            ->groupBy('planilla_id')
            ->sortBy(function ($grupo) {
                return optional($grupo->first()->planilla)->fecha_estimada_entrega;
            });

        // ---------------------------------------------------------------
        // 7) Seleccionar la primera planilla "activa" con elementos pendientes
        // ---------------------------------------------------------------
        $planillaActiva = null;
        foreach ($elementosPorPlanilla as $grupo) {
            $planilla = $grupo->first()->planilla;
            if ($planilla && $planilla->estado !== 'completada') {
                $hayPendientesOSinPaquete = $grupo->contains(function ($elem) {
                    return strtolower($elem->estado) !== 'fabricado'
                        || (strtolower($elem->estado) === 'fabricado' && is_null($elem->paquete_id));
                });

                if ($hayPendientesOSinPaquete) {
                    $planillaActiva = $planilla;
                    break;
                }
            }
        }

        // Nota: Para filtrar elementos a esta planilla activa, descomenta:
        if ($planillaActiva) {
            $elementosMaquina = $elementosMaquina->where('planilla_id', $planillaActiva->id);
        } else {
            $elementosMaquina = collect();
        }


        // E)
        $pesosElementos = $elementosMaquina->map(fn($item) => [
            'id'   => $item->id,
            'peso' => $item->peso,
        ])->values()->toArray();

        $etiquetasData = $elementosMaquina
            ->filter(fn($item) => !empty($item->etiqueta_sub_id))
            ->groupBy('etiqueta_sub_id')
            ->map(fn($grupo, $subId) => [
                'codigo'    => (string) $subId,
                'elementos' => $grupo->pluck('id')->toArray(),
                'pesoTotal' => $grupo->sum('peso'),
            ])
            ->values();

        $elementosReempaquetados = Session::get('elementos_reempaquetados', []);

        // ---------------------------------------------------------------
        // 9) Retornar la vista con todos los datos preparados
        // ---------------------------------------------------------------
        return view('maquinas.show', [
            'maquina'                   => $maquina,
            'ubicacion'                 => $ubicacion,
            'usuario1'                  => $usuario1,
            'usuario2'                  => $usuario2,
            'elementosMaquina'          => $elementosMaquina,
            'pesosElementos'            => $pesosElementos,
            'etiquetasData'             => $etiquetasData,
            'elementosReempaquetados'   => $elementosReempaquetados,
            'maquinas'                  => $maquinas
        ]);
    }



    public function create()
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('maquinas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        return view('maquinas.create');
    }
    // Método para guardar la ubicación en la base de datos
    public function store(Request $request)
    {
        DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
        try {
            // Validación de los datos del formulario
            $request->validate([
                'codigo' => 'required|string|max:6|unique:maquinas,codigo',
                'nombre' => 'required|string|max:40|unique:maquinas,nombre',
                'tipo' => 'string|max:50|in:cortadora_dobladora,ensambladora,soldadora,cortadora manual,dobladora manual ',
                'diametro_min' => 'integer',
                'diametro_max' => 'integer',
                'peso_min' => 'integer',
                'peso_max' => 'integer',
            ], [
                // Mensajes personalizados
                'codigo.required' => 'El campo "código" es obligatorio.',
                'codigo.string' => 'El campo "código" debe ser una cadena de texto.',
                'codigo.max' => 'El campo "código" no puede tener más de 6 caracteres.',
                'codigo.unique' => 'Ya existe una máquina con el mismo código',

                'nombre.required' => 'El campo "nombre" es obligatorio.',
                'nombre.string' => 'El campo "nombre" debe ser una cadena de texto.',
                'nombre.max' => 'El campo "nombre" no puede tener más de 40 caracteres.',
                'nombre.unique' => 'Ya existe una máquina con el mismo nombre',

                'tipo.string' => 'El campo "tpo" debe ser una cadena de texto.',
                'tipo.max' => 'El campo "tipo" no puede tener más de 50 caracteres.',
                'tipo.in' => 'El tipo no está entre los posibles',

                // 'diametro_min.required' => 'El campo "diámetro mínimo" es obligatorio.',
                'diametro_min.integer' => 'El campo "diámetro mínimo" debe ser un número entero.',

                // 'diametro_max.required' => 'El campo "diámetro máximo" es obligatorio.',
                'diametro_max.integer' => 'El campo "diámetro máximo" debe ser un número entero.',

                // 'peso_min.required'     => 'El campo "peso mínimo" es obligatorio.',
                'peso_min.integer' => 'El campo "peso mínimo" debe ser un número entero.',

                //'peso_max.required'     => 'El campo "peso máximo" es obligatorio.',
                'peso_max.integer' => 'El campo "peso máximo" debe ser un número entero.',
            ]);


            // Crear la nueva máquina en la base de datos
            Maquina::create([
                'codigo' => $request->codigo,
                'nombre' => $request->nombre,
                'tipo' => $request->tipo,
                'diametro_min' => $request->diametro_min,
                'diametro_max' => $request->diametro_max,
                'peso_min' => $request->peso_min,
                'peso_max' => $request->peso_max,
            ]);

            DB::commit();  // Confirmamos la transacción
            // Redirigir a la página de listado con un mensaje de éxito
            return redirect()->route('maquinas.index')->with('success', 'Máquina creada con éxito.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Mostrar todos los errores de validación
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            // Mostrar errores generales
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
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
            'codigo' => 'required|string|max:6|unique:maquinas,codigo,' . $id,
            'nombre' => 'required|string|max:40',
            'diametro_min' => 'nullable|integer',
            'diametro_max' => 'nullable|integer',
            'peso_min' => 'nullable|integer',
            'peso_max' => 'nullable|integer',
            'estado' => 'required|string|in:activa,en mantenimiento,inactiva',
        ], [
            'codigo.required' => 'El campo "código" es obligatorio.',
            'codigo.string' => 'El campo "código" debe ser una cadena de texto.',
            'codigo.max' => 'El campo "código" no puede tener más de 6 caracteres.',
            'codigo.unique' => 'El código ya existe, por favor ingrese otro diferente.',

            'nombre.required' => 'El campo "nombre" es obligatorio.',
            'nombre.string' => 'El campo "nombre" debe ser una cadena de texto.',
            'nombre.max' => 'El campo "nombre" no puede tener más de 40 caracteres.',

            'diametro_min.integer' => 'El "diámetro mínimo" debe ser un número entero.',
            'diametro_max.integer' => 'El "diámetro máximo" debe ser un número entero.',
            'peso_min.integer' => 'El "peso mínimo" debe ser un número entero.',
            'peso_max.integer' => 'El "peso máximo" debe ser un número entero.',

            'estado.required' => 'El campo "estado" es obligatorio.',
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
            return redirect()->route('maquinas.index')->with('success', 'La máquina se actualizó correctamente.');
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();

            // Redirigir con un mensaje de error
            return redirect()->back()->with('error', 'Hubo un problema al actualizar la máquina. Intenta nuevamente. Error: ' . $e->getMessage());
        }
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
