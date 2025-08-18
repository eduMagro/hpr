<?php

namespace App\Http\Controllers;

use App\Models\Elemento;
use App\Models\OrdenPlanilla;
use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Maquina;
use App\Models\Alerta;
use App\Models\AsignacionTurno;
use App\Models\turno;
use App\Models\User;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;

class ElementoController extends Controller
{
    /**
     * Aplica los filtros a la consulta de elementos
     */
    private function aplicarFiltros($query, Request $request)
    {

        // 🔢 Filtros específicos
        $filters = [
            'id' => 'id',
            'figura' => 'figura',
            'etiqueta_sub_id' => 'etiqueta_sub_id',

        ];

        foreach ($filters as $requestKey => $column) {
            if ($request->has($requestKey) && $request->$requestKey !== null && $request->$requestKey !== '') {
                $query->where($column, 'like', "%{$request->$requestKey}%");
            }
        }

        // 📅 Filtrado por rango de fechas
        if ($request->has('fecha_inicio') && $request->fecha_inicio) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->has('fecha_finalizacion') && $request->fecha_finalizacion) {
            $query->whereDate('created_at', '<=', $request->fecha_finalizacion);
        }

        if ($request->filled('codigo_planilla')) {
            $input = $request->codigo_planilla;

            $query->whereHas('planilla', function ($q) use ($input) {
                $q->where('codigo', 'like', "%{$input}%");
            });
        }
        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . $request->codigo . '%');
        }

        // Etiqueta
        if ($request->has('etiqueta') && $request->etiqueta) {
            $query->whereHas('etiquetaRelacion', function ($q) use ($request) {
                $q->where('id', $request->etiqueta);
            });
        }
        if ($request->filled('subetiqueta')) {
            $query->where('etiqueta_sub_id', 'like', '%' . $request->subetiqueta . '%');
        }

        // Máquinas
        if ($request->has('maquina') && $request->maquina) {
            $query->whereHas('maquina', function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->maquina}%");
            });
        }

        if ($request->has('maquina_2') && $request->maquina_2) {
            $query->whereHas('maquina_2', function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->maquina_2}%");
            });
        }

        if ($request->has('maquina3') && $request->maquina3) {
            $query->whereHas('maquina_3', function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->maquina3}%");
            });
        }

        // Productos
        if ($request->has('producto1') && $request->producto1) {
            $query->whereHas('producto', function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->producto1}%");
            });
        }

        if ($request->has('producto2') && $request->producto2) {
            $query->whereHas('producto2', function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->producto2}%");
            });
        }

        if ($request->has('producto3') && $request->producto3) {
            $query->whereHas('producto3', function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->producto3}%");
            });
        }

        // Estado
        if ($request->has('estado') && $request->estado) {
            $query->where('estado', 'like', "%{$request->estado}%");
        }
        if ($request->filled('peso')) {
            $query->where('peso', 'like', "%{$request->peso}%");
        }

        if ($request->filled('diametro')) {
            $query->where('diametro', 'like', "%{$request->diametro}%");
        }

        if ($request->filled('longitud')) {
            $query->where('longitud', 'like', "%{$request->longitud}%");
        }

        return $query;
    }
    private function getOrdenamiento(string $columna, string $titulo): string
    {
        $currentSort = request('sort');
        $currentOrder = request('order');
        $isSorted = $currentSort === $columna;
        $nextOrder = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = '';
        if ($isSorted) {
            $icon = $currentOrder === 'asc'
                ? '▲' // flecha hacia arriba
                : '▼'; // flecha hacia abajo
        } else {
            $icon = '⇅'; // símbolo de orden genérico
        }

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="inline-flex items-center space-x-1">' .
            '<span>' . $titulo . '</span><span class="text-xs">' . $icon . '</span></a>';
    }
    /**
     * Ordenamiento seguro para la tabla elementos.
     */
    private function aplicarOrdenamientoElementos($query, Request $request)
    {
        // Todas las columnas que SÍ se pueden ordenar (coinciden con tu array $ordenables)
        $columnasPermitidas = [
            'id',
            'codigo',
            'codigo_planilla',
            'etiqueta',
            'subetiqueta',
            'maquina',
            'maquina_2',
            'maquina3',
            'producto1',
            'producto2',
            'producto3',
            'figura',
            'peso',
            'diametro',
            'longitud',
            'estado',
            'created_at',    // para el orden inicial por fecha
        ];

        // Lee los parámetros y sanea
        $sort  = $request->input('sort', 'created_at');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (!in_array($sort, $columnasPermitidas, true)) {
            $sort = 'created_at';              // fallback seguro
        }

        return $query->orderBy($sort, $order);
    }

    public function index(Request $request)
    {
        $query = Elemento::with([
            'planilla',
            'etiquetaRelacion',
            'maquina',
            'maquina_2',
            'maquina_3',
            'producto',
            'producto2',
            'producto3',
        ])->orderBy('created_at', 'desc');

        $query = $this->aplicarFiltros($query, $request);
        $query = $this->aplicarOrdenamientoElementos($query, $request);
        $totalPesoFiltrado = (clone $query)->sum('peso');
        // Paginación
        $perPage = $request->input('per_page', 10);
        $elementos = $query->paginate($perPage)->appends($request->except('page'));

        // Asegurar relación etiqueta
        $elementos->getCollection()->transform(function ($elemento) {
            $elemento->etiquetaRelacion = $elemento->etiquetaRelacion ?? (object) ['id' => '', 'nombre' => ''];
            return $elemento;
        });

        // Todas las máquinas
        $maquinas = Maquina::all();

        // Definir columnas ordenables para la vista
        $ordenables = [
            'id' => $this->getOrdenamiento('id', 'ID'),
            'codigo' => $this->getOrdenamiento('codigo', 'Código Elemento'),
            'codigo_planilla' => $this->getOrdenamiento('codigo_planilla', 'Planilla'),
            'etiqueta' => $this->getOrdenamiento('etiqueta', 'Etiqueta'),
            'subetiqueta' => $this->getOrdenamiento('subetiqueta', 'SubEtiqueta'),
            'maquina' => $this->getOrdenamiento('maquina', 'Maq. 1'),
            'maquina_2' => $this->getOrdenamiento('maquina_2', 'Maq. 2'),
            'maquina3' => $this->getOrdenamiento('maquina3', 'Maq. 3'),
            'producto1' => $this->getOrdenamiento('producto1', 'M. Prima 1'),
            'producto2' => $this->getOrdenamiento('producto2', 'M. Prima 2'),
            'producto3' => $this->getOrdenamiento('producto3', 'M. Prima 3'),
            'figura' => $this->getOrdenamiento('figura', 'Figura'),
            'peso' => $this->getOrdenamiento('peso', 'Peso (kg)'),
            'diametro' => $this->getOrdenamiento('diametro', 'Diámetro (mm)'),
            'longitud' => $this->getOrdenamiento('longitud', 'Longitud (m)'),
            'estado' => $this->getOrdenamiento('estado', 'Estado'),
        ];

        return view('elementos.index', compact('elementos', 'maquinas', 'ordenables', 'totalPesoFiltrado'));
    }

    public function dividirElemento(Request $request)
    {
        // Validar entrada

        $request->validate([
            'elemento_id' => 'required|exists:elementos,id',
            'num_nuevos' => 'required|integer|min:1',
        ], [
            'elemento_id.required' => 'No se ha seleccionado un elemento válido.',
            'elemento_id.exists' => 'El elemento seleccionado no existe en la base de datos.',
            'num_nuevos.required' => 'Debes indicar cuántos elementos nuevos quieres crear.',
            'num_nuevos.integer' => 'El número de elementos debe ser un valor numérico.',
            'num_nuevos.min' => 'Debes crear al menos un nuevo elemento.',
        ]);

        try {
            // Obtener el elemento original
            $elemento = Elemento::findOrFail($request->elemento_id);

            // Determinar el número total de elementos (X nuevos + 1 original)
            $totalElementos = $request->num_nuevos + 1;

            // Calcular el nuevo peso para cada elemento
            $nuevoPeso = $elemento->peso / $totalElementos;

            // Verificar que el peso sea válido
            if ($nuevoPeso <= 0) {
                return response()->json(['success' => false, 'message' => 'El peso no puede ser 0 o negativo.'], 400);
            }

            // Actualizar el peso del elemento original
            $elemento->update(['peso' => $nuevoPeso]);

            // Crear los nuevos elementos replicando el original
            for ($i = 0; $i < $request->num_nuevos; $i++) {
                $nuevoElemento = $elemento->replicate();
                $nuevoElemento->peso = $nuevoPeso;
                $nuevoElemento->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'El elemento se dividió correctamente en ' . $totalElementos . ' partes'
            ], 200);
        } catch (Exception $e) {
            Log::error('Hubo un error al dividir el elemento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el elemento. Intente nuevamente.'
            ], 500);
        }
    }
    /**
     * Almacena un nuevo elemento en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function solicitarCambioMaquina(Request $request, $elementoId)
    {
        $motivo     = $request->motivo;
        $maquinaId  = $request->maquina_id;
        $horaActual = Carbon::now()->format('H:i:s');

        // Obtener el elemento que solicita el cambio
        $elemento = Elemento::find($elementoId);
        if (!$elemento) {
            return response()->json(['message' => 'Elemento no encontrado.'], 404);
        }

        $etiquetaSubId = $elemento->etiqueta_sub_id;
        $maquinaOrigen = Maquina::find($elemento->maquina_id);
        $maquinaDestino = Maquina::find($maquinaId);

        // Buscar turno actual
        $turno = Turno::where('hora_entrada', '<=', $horaActual)
            ->where('hora_salida', '>=', $horaActual)
            ->first();

        if (!$turno) {
            return response()->json(['message' => 'No se encontró turno activo.'], 404);
        }

        // Buscar asignaciones activas para hoy en la máquina destino
        $asignaciones = AsignacionTurno::where('fecha', Carbon::today())
            ->where('maquina_id', $maquinaId)
            ->where('turno_id', $turno->id)
            ->get();

        if ($asignaciones->isEmpty()) {
            return response()->json(['message' => 'No hay usuarios asignados a esa máquina.'], 404);
        }

        foreach ($asignaciones as $asignacion) {
            $usuarioDestino = User::find($asignacion->user_id);

            $mensaje = "Solicitud de cambio de máquina para elemento #{$elemento->id} (etiqueta {$etiquetaSubId}): {$motivo}. "
                . "Origen: " . ($maquinaOrigen?->nombre ?? 'N/A') . ", Destino: " . ($maquinaDestino?->nombre ?? 'N/A');

            Alerta::create([
                'user_id_1'       => auth()->id(),
                'user_id_2'       => $usuarioDestino->id,
                'destino'         => 'produccion',
                'destinatario'    => $usuarioDestino->name,
                'destinatario_id' => $usuarioDestino->id,
                'mensaje'         => $mensaje,
                'leida'           => false,
                'completada'      => false,
            ]);

            Log::info("Alerta enviada", [
                'elemento_id' => $elemento->id,
                'etiqueta_sub_id' => $etiquetaSubId,
                'usuario_id'  => $usuarioDestino->id,
                'mensaje'     => $mensaje,
            ]);
        }

        return response()->json(['message' => 'Solicitud enviada correctamente al operario asignado.']);
    }

    public function cambioMaquina(Request $request, $id)
    {
        try {
            $request->validate([
                'maquina_id' => 'required|exists:maquinas,id',
            ]);
            Log::info("Entrando al metodo...");
            $elemento = Elemento::findOrFail($id);
            $nuevaMaquinaId = $request->maquina_id;

            if ($elemento->maquina_id == $nuevaMaquinaId) {
                Log::info("El elemento ya pertenece a esa maquina");
            }

            $prefijo = (int) $elemento->etiqueta_sub_id;

            // Buscar hermanos en la nueva máquina con mismo prefijo
            $hermano = Elemento::where('maquina_id', $nuevaMaquinaId)
                ->where('etiqueta_sub_id', 'like', "$prefijo.%")
                ->first();
            Log::info("Buscando a mirmano");

            if ($hermano) {
                $elemento->etiqueta_sub_id = $hermano->etiqueta_sub_id;
            } else {
                $sufijos = Elemento::where('etiqueta_sub_id', 'like', "$prefijo.%")
                    ->pluck('etiqueta_sub_id')
                    ->map(fn($e) => (int) explode('.', $e)[1])
                    ->toArray();
                $next = empty($sufijos) ? 1 : (max($sufijos) + 1);
                $elemento->etiqueta_sub_id = "$prefijo.$next";
            }

            $elemento->maquina_id = $nuevaMaquinaId;
            $elemento->save();
            // Marcar la alerta como completada
            $alertaId = $request->query('alerta_id');

            if ($alertaId) {
                $alerta = Alerta::find($alertaId);
                if ($alerta) {
                    $alerta->completada = true;
                    $alerta->save();
                    Log::info("Alerta {$alertaId} completada");
                }
            }

            return redirect()->route('dashboard')->with('success', 'Cambio de máquina aplicado correctamente.');
        } catch (\Exception $e) {
            Log::error("Error al cambiar máquina de elemento {$id}: {$e->getMessage()}");
            return back()->with('error', 'No se pudo cambiar la máquina del elemento.');
        }
    }

    public function crearSubEtiqueta(Request $request)
    {
        $request->validate([
            'elemento_id' => 'required|exists:elementos,id',
            'cantidad' => 'required|integer|min:1',
            'etiqueta_sub_id' => 'required|string',
        ]);

        try {
            $elemento = Elemento::findOrFail($request->elemento_id);
            $etiquetaBase = Etiqueta::findOrFail($elemento->etiqueta_id);

            if ($request->cantidad > $elemento->barras) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes mover más barras de las que tiene el elemento.',
                ]);
            }

            // Obtener la base alfanumérica del sub_id recibido
            $baseCodigo = explode('.', $request->etiqueta_sub_id)[0]; // ETQ-25-0001.02 → ETQ-25-0001

            // Buscar subetiquetas ya existentes con esa base
            $subExistentes = Etiqueta::where('etiqueta_sub_id', 'like', "$baseCodigo.%")
                ->pluck('etiqueta_sub_id')
                ->toArray();

            // Generar nuevo sub_id disponible
            $nuevoSubId = null;
            for ($i = 1; $i <= 100; $i++) {
                $candidato = $baseCodigo . '.' . str_pad($i, 2, '0', STR_PAD_LEFT);
                if (!in_array($candidato, $subExistentes)) {
                    $nuevoSubId = $candidato;
                    break;
                }
            }

            if (!$nuevoSubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo encontrar una subetiqueta disponible.',
                ]);
            }

            // Crear nueva etiqueta
            $nuevaEtiqueta = $etiquetaBase->replicate();
            $nuevaEtiqueta->etiqueta_sub_id = $nuevoSubId;
            $nuevaEtiqueta->save();

            // Crear nuevo elemento
            $nuevoElemento = $elemento->replicate();
            $nuevoElemento->etiqueta_id = $nuevaEtiqueta->id;
            $nuevoElemento->etiqueta_sub_id = $nuevoSubId;
            $nuevoElemento->barras = $request->cantidad;
            $nuevoElemento->codigo = Elemento::generarCodigo(); // si usas códigos tipo EL-25-001
            $nuevoElemento->save();

            // Actualizar o eliminar el elemento original
            $elemento->barras -= $request->cantidad;
            if ($elemento->barras <= 0) {
                $elemento->delete();
            } else {
                $elemento->save();
            }

            return response()->json([
                'success' => true,
                'message' => "Subetiqueta $nuevoSubId y nuevo elemento creados correctamente.",
            ]);
        } catch (\Exception $e) {
            Log::error('Error al dividir elemento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'conjunto_id' => 'required|exists:conjuntos,id',
            'nombre' => 'required|string|max:255',
            'cantidad' => 'required|integer|min:1',
            'diametro' => 'required|numeric|min:0',
            'longitud' => 'required|numeric|min:0',
            'peso' => 'required|numeric|min:0',
        ]);

        Elemento::create($validated);

        return redirect()->route('elementos.index')->with('success', 'Elemento creado exitosamente.');
    }

    public function showByEtiquetas($planillaId)
    {

        $planilla = Planilla::with(['elementos'])->findOrFail($planillaId);

        // Obtener elementos clasificados por etiquetas
        $etiquetasConElementos = Etiqueta::with('elementos')
            ->whereHas('elementos', function ($query) use ($planillaId) {
                $query->where('planilla_id', $planillaId);
            })
            ->get();

        return view('elementos.show', compact('planilla', 'etiquetasConElementos'));
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('Datos antes de validar:', ['data' => $request]);

            // Validar los datos recibidos con mensajes personalizados
            $validated = $request->validate([
                'planilla_id'   => 'nullable|integer|exists:planillas,id',
                'etiqueta_id'   => 'nullable|integer|exists:etiquetas,id',
                'maquina_id'    => 'nullable|integer|exists:maquinas,id',
                'maquina_id_2'  => 'nullable|integer|exists:maquinas,id',
                'maquina_id_3'  => 'nullable|integer|exists:maquinas,id',
                'producto_id'   => 'nullable|integer|exists:productos,id',
                'producto_id_2' => 'nullable|integer|exists:productos,id',
                'producto_id_3' => 'nullable|integer|exists:productos,id',
                'figura'        => 'nullable|string|max:255',
                'fila'          => 'nullable|string|max:255',
                'marca'         => 'nullable|string|max:255',
                'etiqueta'      => 'nullable|string|max:255',
                'diametro'      => 'nullable|numeric',
                'peso'      => 'nullable|numeric',
                'longitud'      => 'nullable|numeric',
                'estado'        => 'nullable|string|max:50'
            ], [
                'planilla_id.integer'   => 'El campo planilla_id debe ser un número entero.',
                'planilla_id.exists'    => 'La planilla especificada en planilla_id no existe.',
                'etiqueta_id.integer'   => 'El campo etiqueta_id debe ser un número entero.',
                'etiqueta_id.exists'    => 'La etiqueta especificada en etiqueta_id no existe.',
                'maquina_id.integer'    => 'El campo maquina_id debe ser un número entero.',
                'maquina_id.exists'     => 'La máquina especificada en maquina_id no existe.',
                'maquina_id_2.integer'  => 'El campo maquina_id_2 debe ser un número entero.',
                'maquina_id_2.exists'   => 'La máquina especificada en maquina_id_2 no existe.',
                'maquina_id_3.integer'  => 'El campo maquina_id_3 debe ser un número entero.',
                'maquina_id_3.exists'   => 'La máquina especificada en maquina_id_3 no existe.',
                'producto_id.integer'   => 'El campo producto_id debe ser un número entero.',
                'producto_id.exists'    => 'El producto especificado en producto_id no existe.',
                'producto_id_2.integer' => 'El campo producto_id_2 debe ser un número entero.',
                'producto_id_2.exists'  => 'El producto especificado en producto_id_2 no existe.',
                'producto_id_3.integer' => 'El campo producto_id_3 debe ser un número entero.',
                'producto_id_3.exists'  => 'El producto especificado en producto_id_3 no existe.',
                'figura.string'         => 'El campo figura debe ser una cadena de texto.',
                'figura.max'            => 'El campo figura no debe tener más de 255 caracteres.',
                'fila.string'           => 'El campo fila debe ser una cadena de texto.',
                'fila.max'              => 'El campo fila no debe tener más de 255 caracteres.',
                'marca.string'          => 'El campo marca debe ser una cadena de texto.',
                'marca.max'             => 'El campo marca no debe tener más de 255 caracteres.',
                'etiqueta.string'       => 'El campo etiqueta debe ser una cadena de texto.',
                'etiqueta.max'          => 'El campo etiqueta no debe tener más de 255 caracteres.',
                'diametro.numeric'      => 'El campo diametro debe ser un número.',
                'peso.numeric'      => 'El campo peso debe ser un número.',
                'longitud.numeric'      => 'El campo longitud debe ser un número.',
                'estado.string'         => 'El campo estado debe ser una cadena de texto.',
                'estado.max'            => 'El campo estado no debe tener más de 50 caracteres.',
            ]);


            // Registrar los datos validados antes de actualizar
            Log::info('Datos antes de actualizar:', ['data' => $validated]);

            $elemento = Elemento::findOrFail($id);

            // 🚚 Si cambió la máquina, recalcular etiqueta_sub_id
            if (
                array_key_exists('maquina_id', $validated)
                && $validated['maquina_id'] != $elemento->maquina_id
            ) {
                $nuevoMaquinaId = $validated['maquina_id'];
                $prefijo = (int) $elemento->etiqueta_sub_id; // parte antes del punto

                // 1) Buscar hermanos en la máquina destino con ese mismo prefijo
                $hermano = Elemento::where('maquina_id', $nuevoMaquinaId)
                    ->where('etiqueta_sub_id', 'like', "$prefijo.%")
                    ->first();

                if ($hermano) {
                    // Si existe, reutilizar la misma etiqueta_sub_id
                    $validated['etiqueta_sub_id'] = $hermano->etiqueta_sub_id;
                } else {
                    // 2) No hay hermanos; generar siguiente sufijo libre
                    $sufijos = Elemento::where('etiqueta_sub_id', 'like', "$prefijo.%")
                        ->pluck('etiqueta_sub_id')
                        ->map(function ($full) use ($prefijo) {
                            return (int) explode('.', $full)[1];
                        })
                        ->toArray();

                    $next = empty($sufijos) ? 1 : (max($sufijos) + 1);
                    $validated['etiqueta_sub_id'] = "$prefijo.$next";
                }
            }

            // Actualizar resto de campos
            $elemento->update($validated);
            // Si cambió de máquina, actualizar orden_planillas
            if (array_key_exists('maquina_id', $validated) && $validated['maquina_id'] != $elemento->getOriginal('maquina_id')) {
                $planillaId = $elemento->planilla_id;
                $nuevaMaquinaId = $validated['maquina_id'];
                $maquinaAnteriorId = $elemento->getOriginal('maquina_id');

                // 1. Insertar en nueva máquina si no existe
                $existe = OrdenPlanilla::where('planilla_id', $planillaId)
                    ->where('maquina_id', $nuevaMaquinaId)
                    ->exists();

                if (!$existe) {
                    $ultimaPosicion = OrdenPlanilla::where('maquina_id', $nuevaMaquinaId)->max('posicion') ?? 0;

                    OrdenPlanilla::create([
                        'planilla_id' => $planillaId,
                        'maquina_id' => $nuevaMaquinaId,
                        'posicion' => $ultimaPosicion + 1,
                    ]);
                }

                // 2. Eliminar de la máquina anterior si ya no hay elementos
                $quedan = \App\Models\Elemento::where('planilla_id', $planillaId)
                    ->where('maquina_id', $maquinaAnteriorId)
                    ->exists();

                if (!$quedan) {
                    OrdenPlanilla::where('planilla_id', $planillaId)
                        ->where('maquina_id', $maquinaAnteriorId)
                        ->delete();
                }
            }
            // Registrar el estado del elemento después de actualizar
            Log::info('Elemento después de actualizar:', ['data' => $elemento->toArray()]);

            return response()->json([
                'success' => true,
                'message' => 'Elemento actualizado correctamente',
                'data'    => $elemento
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error("Elemento con ID {$id} no encontrado", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Elemento no encontrado'
            ], 404);
        } catch (ValidationException $e) {
            Log::error('Error de validación', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error("Error al actualizar el elemento con ID {$id}", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el elemento. Intente nuevamente.'
            ], 500);
        }
    }

    /**
     * Elimina un elemento existente de la base de datos.
     *
     * @param  \App\Models\Elemento  $elemento
     * @return \Illuminate\Http\Response
     */
    public function destroy(Elemento $elemento)
    {
        $elemento->delete();
        return redirect()->route('elementos.index')->with('success', 'Elemento eliminado exitosamente.');
    }
}
