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

class ElementoController extends Controller
{
    public function index(Request $request)
    {
        $query = Elemento::with([
            'planilla',
            'etiquetaRelacion',
            'maquina',
            'producto',
            'user',
            'user2'
        ])->orderBy('created_at', 'desc'); // Ordenar por fecha de creación descendente

        // Aplicar los filtros utilizando un método separado
        $query = $this->aplicarFiltros($query, $request);

        $elementos = $query->paginate(10);

        return view('elementos.index', compact('elementos'));
    }

    /**
     * Aplica los filtros a la consulta de elementos
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_finalizacion')) {
            $query->whereBetween('created_at', [$request->fecha_inicio, $request->fecha_finalizacion]);
        } elseif ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        } elseif ($request->filled('fecha_finalizacion')) {
            $query->whereDate('created_at', '<=', $request->fecha_finalizacion);
        }

        if ($request->filled('codigo_planilla')) {
            $query->whereHas('planilla', function ($q) use ($request) {
                $q->where('codigo', 'like', '%' . $request->codigo_planilla . '%');
            });
        }

        if ($request->filled('usuario1')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->usuario1 . '%');
            });
        }

        if ($request->filled('etiqueta')) {
            $query->whereHas('etiquetaRelacion', function ($q) use ($request) {
                $q->where('id', $request->etiqueta);
            });
        }

        if ($request->filled('maquina')) {
            $query->whereHas('maquina', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->maquina . '%');
            });
        }

        if ($request->filled('producto1')) {
            $query->whereHas('producto', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->producto1 . '%');
            });
        }

        if ($request->filled('producto2')) {
            $query->whereHas('producto', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->producto2 . '%');
            });
        }

        if ($request->filled('figura')) {
            $query->where('figura', 'like', '%' . $request->figura . '%');
        }

        return $query;
    }



    /**
     * Muestra el formulario para crear un nuevo elemento.
     *
     * @return \Illuminate\Http\Response
     */


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


    /**
     * Muestra el formulario para editar un elemento existente.
     *
     * @param  \App\Models\Elemento  $elemento
     * @return \Illuminate\Http\Response
     */
    public function edit(Elemento $elemento)
    {
        $conjuntos = Conjunto::with('planilla')->get();
        return view('elementos.edit', compact('elemento', 'conjuntos'));
    }



    public function actualizarElemento(Request $request, $id, $maquina_id)
    {
        // Iniciar una transacción para asegurar la integridad de los datos
        DB::beginTransaction();

        try {
            //Obtener elemento
            $elemento = Elemento::with(['maquina', 'planilla'])->findOrFail($id);


            // Obtener la planilla directamente desde la relación
            $planilla = $elemento->planilla; // ✅ Usar la relación en lugar de buscarlo en la DB

            $maquina = $elemento->maquina;

            if (!$maquina) {
                return redirect()->route('elementos.show', $planilla->id)
                    ->with('error', 'La máquina asociada al elemento no existe.');
            }

            $productosConsumidos = [];
            $producto1 = null;
            $producto2 = null;

            if ($elemento->estado == "pendiente") {


                $productos = $maquina->productos()
                    ->where('diametro', $elemento->diametro)
                    ->orderBy('peso_stock')
                    ->get();

                if ($productos->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'En esta máquina no hay materia prima con ese diámetro.',
                    ], 400);
                }
                $elemento->estado = "fabricando";
                $elemento->fecha_inicio = now();
                $elemento->users_id = Auth::id();
                $elemento->users_id_2 = session()->get('compañero_id', null);
                $elemento->save();
            } elseif ($elemento->estado == "fabricando") {

                $productos = $maquina->productos()
                    ->where('diametro', $elemento->diametro)
                    ->orderBy('peso_stock')
                    ->get();
                $pesoRequerido = $elemento->peso;

                if ($pesoRequerido <= 0) {
                    return response()->json([
                        'success' => false,
                        'error' => 'El peso de la etiqueta es 0, no es necesario consumir materia prima.',
                    ], 400);
                }

                foreach ($productos as $prod) {
                    if ($pesoRequerido <= 0) {
                        break;
                    }

                    $pesoDisponible = $prod->peso_stock;
                    if ($pesoDisponible > 0) {
                        $restar = min($pesoDisponible, $pesoRequerido);
                        $prod->peso_stock -= $restar;

                        if ($prod->peso_stock == 0) {
                            $prod->estado = "consumido";
                        }

                        $prod->save();
                        $productosConsumidos[] = $prod;
                        $pesoRequerido -= $restar;
                    }
                }

                if ($pesoRequerido > 0) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No hay suficiente materia prima. Avisa al gruista.',
                    ], 400);
                }

                $elemento->fecha_finalizacion = now();
                $elemento->estado = 'completado';
                // Verificar código de máquina y ubicación
                $codigoMaquina = $elemento->maquina->codigo;
                $ubicacion = $this->obtenerUbicacionPorCodigoMaquina($codigoMaquina);
                $elemento->ubicacion_id = $ubicacion->id;
                // ✅ Se asignan solo si existen productos consumidos
                $producto1 = isset($productosConsumidos[0]) ? $productosConsumidos[0] : null;
                $producto2 = isset($productosConsumidos[1]) ? $productosConsumidos[1] : null;

                $elemento->producto_id = $producto1 ? $producto1->id : null;
                $elemento->producto_id_2 = $producto2 ? $producto2->id : null;
                $elemento->save();
            } elseif ($elemento->estado == "completado") {
                $producto1 = $elemento->producto_id ? $maquina->productos()->find($elemento->producto_id) : null;
                $producto2 = $elemento->producto_id_2 ? $maquina->productos()->find($elemento->producto_id_2) : null;

                if (!$producto1 && !$producto2) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No se encontraron los productos consumidos para restaurar.',
                    ], 400);
                }

                $pesoRestaurar = $elemento->peso;

                if ($producto1) {
                    $pesoIncremento = min($pesoRestaurar, $producto1->peso_inicial - $producto1->peso_stock);
                    $producto1->peso_stock += $pesoIncremento;
                    $pesoRestaurar -= $pesoIncremento;
                    $producto1->estado = "fabricando";
                    $producto1->save();
                }

                if ($pesoRestaurar > 0 && $producto2) {
                    $pesoIncremento = min($pesoRestaurar, $producto2->peso_inicial - $producto2->peso_stock);
                    $producto2->peso_stock += $pesoIncremento;
                    $pesoRestaurar -= $pesoIncremento;
                    $producto2->estado = "fabricando";
                    $producto2->save();
                }

                // Resetear la etiqueta a estado "pendiente"
                $elemento->fecha_inicio = null;
                $elemento->fecha_finalizacion = null;
                $elemento->estado = "pendiente";
                $elemento->users_id = null;
                $elemento->users_id_2 = null;
                $elemento->producto_id = null;
                $elemento->producto_id_2 = null;
                $elemento->save();
            }

            DB::commit();

            // ✅ Se asegura de llenar `productosAfectados` en TODAS las transiciones
            $productosAfectados = [];

            if ($producto1 && isset($producto1->id)) {
                $productosAfectados[] = [
                    'id' => $producto1->id,
                    'peso_stock' => $producto1->peso_stock,
                    'peso_inicial' => $producto1->peso_inicial
                ];
            }

            if ($producto2 && isset($producto2->id)) {
                $productosAfectados[] = [
                    'id' => $producto2->id,
                    'peso_stock' => $producto2->peso_stock,
                    'peso_inicial' => $producto2->peso_inicial
                ];
            }

            return response()->json([
                'success' => true,
                'estado' => $elemento->estado,
                'fecha_inicio' => $elemento->fecha_inicio ? Carbon::parse($elemento->fecha_inicio)->format('d/m/Y H:i:s') : 'No asignada',
                'fecha_finalizacion' => $elemento->fecha_finalizacion ? Carbon::parse($elemento->fecha_finalizacion)->format('d/m/Y H:i:s') : 'No asignada',
                'productos_afectados' => $productosAfectados
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Obtiene la ubicación según el código de máquina.
     */
    private function obtenerUbicacionPorCodigoMaquina($codigoMaquina)
    {
        try {
            return Ubicacion::where('descripcion', 'LIKE', "%$codigoMaquina%")->first();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ubicación de la máquina: ' . $e->getMessage()
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

    public function update(Request $request, $id)
{
    $elemento = Elemento::findOrFail($id);

    // Asegurar que se recibe JSON correctamente
    $data = $request->json()->all();

    $elemento->update($data);

    return response()->json(['success' => true, 'message' => 'Planilla actualizada correctamente']);
}

}
