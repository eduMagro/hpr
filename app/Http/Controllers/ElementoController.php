<?php

namespace App\Http\Controllers;

use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Maquina;
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
        // 🔍 Búsqueda global en múltiples campos
        if ($request->has('buscar') && $request->buscar) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('id', 'like', "%$buscar%")
                    ->orWhere('figura', 'like', "%$buscar%")
                    ->orWhere('subetiquetas', 'like', "%$buscar%")
                    ->orWhereHas('planilla', function ($q) use ($buscar) {
                        $q->where('codigo', 'like', "%$buscar%");
                    })
                    ->orWhereHas('user', function ($q) use ($buscar) {
                        $q->where('name', 'like', "%$buscar%");
                    })
                    ->orWhereHas('user2', function ($q) use ($buscar) {
                        $q->where('name', 'like', "%$buscar%");
                    })
                    ->orWhereHas('maquina', function ($q) use ($buscar) {
                        $q->where('nombre', 'like', "%$buscar%");
                    })
                    ->orWhereHas('maquina_2', function ($q) use ($buscar) {
                        $q->where('nombre', 'like', "%$buscar%");
                    })
                    ->orWhereHas('maquina_3', function ($q) use ($buscar) {
                        $q->where('nombre', 'like', "%$buscar%");
                    });
            });
        }

        // 🔢 Filtros específicos
        $filters = [
            'id' => 'id',
            'figura' => 'figura',
            'subetiquetas' => 'subetiqueta',
            'paquete_id' => 'paquete_id',
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

        // 🧩 Relaciones con otras tablas

        // Planilla
        if ($request->has('codigo_planilla') && $request->codigo_planilla) {
            $query->whereHas('planilla', function ($q) use ($request) {
                $q->where('codigo', 'like', "%{$request->codigo_planilla}%");
            });
        }

        // Usuario 1
        if ($request->has('usuario1') && $request->usuario1) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->usuario1}%");
            });
        }

        // Usuario 2
        if ($request->has('usuario2') && $request->usuario2) {
            $query->whereHas('user2', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->usuario2}%");
            });
        }

        // Etiqueta
        if ($request->has('etiqueta') && $request->etiqueta) {
            $query->whereHas('etiquetaRelacion', function ($q) use ($request) {
                $q->where('id', $request->etiqueta);
            });
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

        // 🏷️ Ordenación dinámica
        $allowedSortColumns = ['created_at', 'id', 'figura', 'subetiqueta', 'paquete_id'];

        $sortBy = $request->filled('sort_by') && in_array($request->input('sort_by'), $allowedSortColumns)
            ? $request->input('sort_by')
            : 'created_at'; // Default seguro

        $order = $request->filled('order') && in_array($request->input('order'), ['asc', 'desc'])
            ? $request->input('order')
            : 'desc'; // Default seguro

        $query->orderBy($sortBy, $order);

        return $query;
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
            'paquete',
            'user',
            'user2'
        ])->orderBy('created_at', 'desc'); // Ordenar por fecha de creación descendente

        // Aplicar los filtros utilizando un método separado
        $query = $this->aplicarFiltros($query, $request);

        // Aplicar paginación y mantener filtros en la URL
        $elementos = $query->paginate(10)->appends($request->query());

        // Asegurar que etiquetaRelacion siempre tenga un objeto válido
        $elementos->getCollection()->transform(function ($elemento) {
            $elemento->etiquetaRelacion = $elemento->etiquetaRelacion ?? (object) ['id' => '', 'nombre' => ''];
            return $elemento;
        });

        // Obtener todas las máquinas de la tabla "maquinas"
        $maquinas = Maquina::all();

        // Pasar las variables a la vista
        return view('elementos.index', compact('elementos', 'maquinas'));
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

    /**
     * Muestra un elemento específico.
     *
     * @param  \App\Models\Elemento  $elemento
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        $planillas = Planilla::with([
            'paquetes:id,planilla_id,peso,ubicacion_id',
            'paquetes.ubicacion:id', // Cargar la ubicación de cada paquete
            'etiquetas:id,planilla_id,estado,peso,paquete_id',
            'elementos:id,planilla_id,estado,peso,ubicacion_id,etiqueta_id,paquete_id,maquina_id',
            'elementos.ubicacion:id,nombre', // Cargar la ubicación de cada elemento
            'elementos.maquina:id,nombre' // Cargar la máquina de cada elemento
        ])->get();

        // Función para asignar color de fondo según estado
        $getColor = function ($estado, $tipo) {
            $estado = strtolower($estado ?? 'desconocido');

            if ($tipo === 'etiqueta' && $estado === 'completada') {
                $estado = 'completado';
            }

            return match ($estado) {
                'completado' => 'bg-green-200',
                'pendiente' => 'bg-red-200',
                'fabricando' => 'bg-blue-200',
                default => 'bg-gray-200'
            };
        };

        // Procesar cada planilla
        $planillasCalculadas = $planillas->map(function ($planilla) use ($getColor) {
            $pesoAcumulado = $planilla->elementos->where('estado', 'completado')->sum('peso');
            $pesoTotal = max(1, $planilla->peso_total ?? 1);
            $progreso = min(100, ($pesoAcumulado / $pesoTotal) * 100);

            $paquetes = $planilla->paquetes->map(function ($paquete) use ($getColor) {
                $paquete->color = $getColor($paquete->estado, 'paquete');
                return $paquete;
            });

            $elementos = $planilla->elementos->map(function ($elemento) use ($getColor) {
                $elemento->color = $getColor($elemento->estado, 'elemento');
                return $elemento;
            });

            $etiquetas = $planilla->etiquetas->map(function ($etiqueta) use ($getColor, $elementos) {
                $etiqueta->color = $getColor($etiqueta->estado, 'etiqueta');
                $etiqueta->elementos = $elementos->where('etiqueta_id', $etiqueta->id);
                return $etiqueta;
            });

            return [
                'planilla' => $planilla,
                'pesoAcumulado' => $pesoAcumulado,
                'pesoRestante' => max(0, $pesoTotal - $pesoAcumulado),
                'progreso' => round($progreso, 2),
                'paquetes' => $paquetes,
                'etiquetas' => $etiquetas,
                'elementos' => $elementos,
                'etiquetasSinPaquete' => $etiquetas->whereNull('paquete_id')
            ];
        });

        return view('elementos.show', compact('planillasCalculadas'));
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
                'users_id'      => 'nullable|integer|exists:users,id',
                'users_id_2'    => 'nullable|integer|exists:users,id',
                'planilla_id'   => 'nullable|integer|exists:planillas,id',
                'etiqueta_id'   => 'nullable|integer|exists:etiquetas,id',
                'paquete_id'    => 'nullable|integer|exists:paquetes,id',
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
                'users_id.integer'      => 'El campo users_id debe ser un número entero.',
                'users_id.exists'       => 'El usuario especificado en users_id no existe.',
                'users_id_2.integer'    => 'El campo users_id_2 debe ser un número entero.',
                'users_id_2.exists'     => 'El usuario especificado en users_id_2 no existe.',
                'planilla_id.integer'   => 'El campo planilla_id debe ser un número entero.',
                'planilla_id.exists'    => 'La planilla especificada en planilla_id no existe.',
                'etiqueta_id.integer'   => 'El campo etiqueta_id debe ser un número entero.',
                'etiqueta_id.exists'    => 'La etiqueta especificada en etiqueta_id no existe.',
                'paquete_id.integer'    => 'El campo paquete_id debe ser un número entero.',
                'paquete_id.exists'     => 'El paquete especificado en paquete_id no existe.',
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
            Log::info('Datos antes de actualizar:', ['data' => $validatedData]);

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
