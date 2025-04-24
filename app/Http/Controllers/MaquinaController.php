<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
use App\Models\Etiqueta;
use App\Models\Elemento;

use App\Models\User;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class MaquinaController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = User::where('id', '!=', auth()->id())
            ->where('rol', 'operario')
            ->get();

        $query = Maquina::with('productos')
            ->selectRaw('maquinas.*, (
                SELECT COUNT(*) FROM elementos 
                WHERE elementos.maquina_id_2 = maquinas.id
            ) as elementos_ensambladora')
            ->withCount(['elementos as elementos_count' => function ($query) {
                $query->where('estado', '!=', 'completado');
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
        $datosDepuracion = $registrosMaquina->map(function ($maquina) {
            return [
                'id' => $maquina->id,
                'nombre' => $maquina->nombre,
                'tipo' => $maquina->tipo,
                'elementos_count' => $maquina->elementos_count,
                'elementos_ensambladora' => $maquina->elementos_ensambladora,
            ];
        });
        // dd($datosDepuracion->toArray());

        // Pasar las máquinas y usuarios a la vista
        return view('maquinas.index', compact('registrosMaquina', 'usuarios'));
    }




    //------------------------------------------------------------------------------------ SHOW
    public function show($id)
    {
        // 1) Cargar la máquina y sus relaciones
        $maquina = Maquina::with([
            'elementos.planilla',
            'elementos.etiquetaRelacion',
            'productos'
        ])->findOrFail($id);

        $ubicacion = Ubicacion::where('descripcion', 'like', '%' . $maquina->codigo . '%')->first();

        $usuario1 = auth()->user();
        $usuario2 = session('compañero_id') ? User::find(session('compañero_id')) : null;

        // Decodificar nombres (por tu lógica)
        $usuario1->name = html_entity_decode($usuario1->name, ENT_QUOTES, 'UTF-8');
        if ($usuario2) {
            $usuario2->name = html_entity_decode($usuario2->name, ENT_QUOTES, 'UTF-8');
        }

        // 2) Verificar si la máquina es "IDEA 5"
        $maquinaIdea5 = Maquina::whereRaw('LOWER(nombre) = ?', ['idea 5'])->first();

        // 3) Cargar la colección base de elementos para ESTA máquina
        $elementosMaquina = $maquina->elementos;
        $elementosMaquina = $elementosMaquina->load('etiquetaRelacion');

        // 4) Si estamos en "IDEA 5", fusionar elementos que tengan maquina_id_2 = Idea5 (y otra maquina principal)
        if ($maquinaIdea5 && $maquina->id == $maquinaIdea5->id) {
            $elementosExtra = Elemento::where('maquina_id_2', $maquinaIdea5->id)
                ->where('maquina_id', '!=', $maquinaIdea5->id)
                ->get();
            $elementosMaquina = $elementosMaquina->merge($elementosExtra);
        }

        // ---------------------------------------------------------------
        // A) AGRUPAR POR PLANILLA LOS ELEMENTOS DE ESTA MÁQUINA
        //    (solo se agrupan planillas que de verdad tienen elementos en esta máquina).
        // ---------------------------------------------------------------
        $elementosPorPlanilla = $elementosMaquina
            ->groupBy('planilla_id')
            ->sortBy(function ($grupo) {
                // Ordenar por fecha_estimada_entrega de la planilla
                return optional($grupo->first()->planilla)->fecha_estimada_entrega;
            });

        // ---------------------------------------------------------------
        // B) SELECCIONAR LA PRIMERA PLANILLA "NO COMPLETADA" CON ELEMENTOS PENDIENTES
        //    Esto significa: planilla->estado != 'Completada'
        //    (o tu propia lógica) y que haya al menos un elemento sin completar
        // ---------------------------------------------------------------
        $planillaActiva = null;
        foreach ($elementosPorPlanilla as $planillaId => $grupo) {
            $planilla = $grupo->first()->planilla;

            // Aquí asumes que si planilla->estado != 'Completada' => No está terminada
            // (Ajusta según tu DB: 'Pendiente', 'En proceso', etc.)
            if ($planilla && $planilla->estado !== 'Completada') {
                // Opcional: verifica si en ese grupo hay AL MENOS un elemento sin estado 'completado'
                // (si no quieres filtrar por elemento, quita esta parte)
                $hayElementosPendientes = $grupo->contains(function ($elem) {
                    return strtolower($elem->estado) !== 'completado';
                });

                if ($hayElementosPendientes) {
                    $planillaActiva = $planilla;
                    break; // la primera que encontramos
                }
            }
        }

        // ---------------------------------------------------------------
        // C) QUEDARNOS SOLO CON LOS ELEMENTOS DE ESTA PLANILLA ACTIVA
        //    (si no hay planilla activa, quedará vacío)
        // ---------------------------------------------------------------
        if ($planillaActiva) {
            $elementosMaquina = $elementosMaquina->filter(function ($elem) use ($planillaActiva) {
                return $elem->planilla_id == $planillaActiva->id;
            });
        } else {
            // No hay planilla pendiente para esta máquina => sin elementos
            $elementosMaquina = collect();
        }

        // El resto de la lógica parte ya de LOS ELEMENTOS de la planilla activa
        // ---------------------------------------------------------------

        // 6) Recolectar etiquetas
        $etiquetasIds = $elementosMaquina->pluck('etiqueta_id')->unique();

        // 7) Otros elementos (siempre que necesites mostrarlos, ajusta tu lógica)
        $otrosElementos = Elemento::with('maquina')
            ->whereIn('etiqueta_id', $etiquetasIds)
            ->where('maquina_id', '!=', $maquina->id);

        // Si la máquina actual es la Idea5, excluimos los de maquina_id_2 = Idea5
        if ($maquinaIdea5) {
            $otrosElementos = $otrosElementos->where(function ($query) use ($maquinaIdea5) {
                $query->where('maquina_id_2', '!=', $maquinaIdea5->id)
                    ->orWhereNull('maquina_id_2');
            });
        }
        $otrosElementos = $otrosElementos->get()->groupBy('etiqueta_id');

        // 8) Etiquetas con elementos en una sola máquina
        $etiquetasEnUnaSolaMaquina = Elemento::whereIn('etiqueta_id', $etiquetasIds)
            ->selectRaw('etiqueta_id, COUNT(DISTINCT maquina_id) as total_maquinas')
            ->groupBy('etiqueta_id')
            ->having('total_maquinas', 1)
            ->pluck('etiqueta_id');

        // 9) Elementos de esas etiquetas
        $elementosEnUnaSolaMaquina = Elemento::whereIn('etiqueta_id', $etiquetasEnUnaSolaMaquina)
            ->with('maquina')
            ->get();

        // Fusionar con elementos de maquina_id_2 = Idea5
        if ($maquinaIdea5) {
            $elementosExtra = Elemento::where('maquina_id_2', $maquinaIdea5->id)->get();
            $elementosEnUnaSolaMaquina = $elementosEnUnaSolaMaquina->merge($elementosExtra);
        }

        // 10) Preparar datos de pesos
        $pesosElementos = $elementosMaquina->map(function ($item) {
            return [
                'id'   => $item->id,
                'peso' => $item->peso,
            ];
        })->values()->toArray();

        $etiquetasData = $elementosMaquina
            ->groupBy('etiqueta_id')
            ->map(function ($grupo, $etiquetaId) {
                $etiqueta = optional($grupo->first()->etiqueta); // safe null
                return [
                    'codigo'    => $etiqueta->etiqueta_sub_id ?? 'sin-codigo',
                    'elementos' => $grupo->pluck('id')->toArray(),
                    'pesoTotal' => $grupo->sum('peso'),
                ];
            })
            ->filter(fn($data) => $data['codigo'] !== 'sin-codigo') // elimina nulos
            ->values();


        //Cogemos los elementos reenpaquetados para la ensambladora
        $elementosReempaquetados = session('elementos_reempaquetados', []);

        // 13) Retornar la vista (asegúrate de usar `$elementosMaquina` en la vista)
        return view('maquinas.show', [
            'maquina'                   => $maquina,
            'ubicacion'                 => $ubicacion,
            'usuario1'                  => $usuario1,
            'usuario2'                  => $usuario2,
            'otrosElementos'            => $otrosElementos,
            'etiquetasEnUnaSolaMaquina' => $etiquetasEnUnaSolaMaquina,
            'elementosEnUnaSolaMaquina' => $elementosEnUnaSolaMaquina,
            'elementosMaquina'          => $elementosMaquina, // Ya está filtrado a la planilla activa
            'pesosElementos'            => $pesosElementos,
            'etiquetasData'             => $etiquetasData,
            'elementosReempaquetados'   => $elementosReempaquetados
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
                'tipo' => 'required|string|max:50|in:cortadora_dobladora,ensambladora,soldadora,cortadora manual,dobladora manual ',
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

                'tipo.required' => 'El campo "tipo" es obligatorio.',
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
