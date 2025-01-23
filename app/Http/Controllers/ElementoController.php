<?php

namespace App\Http\Controllers;

use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\Etiqueta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;



class ElementoController extends Controller
{
    /**
     * Muestra una lista de todos los elementos.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $elementos = Elemento::with('conjunto.planilla')->paginate(10);
        return view('elementos.index', compact('elementos'));
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
        // Encuentra la planilla por ID y carga las relaciones necesarias (elementos y sus máquinas)
        $planilla = Planilla::with('elementos.maquina')->findOrFail($id);

        // Obtiene los elementos relacionados con la planilla
        $elementos = $planilla->elementos;

        // Retorna la vista con la planilla y sus elementos
        return view('elementos.show', compact('planilla', 'elementos'));
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

    /**
     * Actualiza un elemento existente en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Elemento  $elemento
     * @return \Illuminate\Http\Response
     */

    public function actualizarEstado(Request $request)
    {
        // Iniciar una transacción para asegurar la integridad de los datos
        DB::beginTransaction();

        try {
            // Validar los datos enviados por el formulario
            $validated = $request->validate([
                'elemento_id' => 'required|exists:elementos,id',
                'planilla_id' => 'required|exists:planillas,id',
                'accion' => 'required|in:completar,descompletar',
            ]);

            // Buscar el elemento, la planilla y la máquina asociados
            $elemento = Elemento::findOrFail($validated['elemento_id']);
            $planilla = Planilla::findOrFail($validated['planilla_id']);
            $maquina = $elemento->maquina;

            if (!$maquina) {
                return redirect()->route('elementos.show', $planilla->id)
                    ->with('error', 'La máquina asociada al elemento no existe.');
            }

            if ($validated['accion'] === 'completar') {
                // Obtener todos los productos con el diámetro especificado
                $productos = $maquina->productos()->where('diametro', $elemento->diametro)->orderBy('id')->get();

                if ($productos->isEmpty()) {
                    return redirect()->route('elementos.show', $planilla->id)
                        ->with('error', 'No se encontraron productos asociados con ese diámetro en la máquina.');
                }

                $pesoRequerido = $elemento->peso;

                foreach ($productos as $producto) {
                    if ($pesoRequerido <= 0) {
                        break;
                    }

                    // Verificar cuánto peso se puede restar de este producto
                    $pesoDisponible = $producto->peso_stock;

                    if ($pesoDisponible > 0) {
                        $resta = min($pesoDisponible, $pesoRequerido);
                        $producto->peso_stock -= $resta;
                        $producto->save();

                        $pesoRequerido -= $resta;
                    }
                }

                if ($pesoRequerido > 0) {
                    throw new \Exception('No hay materia prima suficiente en la máquina.');
                }

                // Actualizar el estado del elemento
                $elemento->estado = 'completado';
                $elemento->users_id = auth()->id();
                $elemento->producto_id = $productos->first()->id; // Asociar el primer producto usado
                $elemento->save();

                DB::commit();

                return redirect()->route('elementos.show', $planilla->id)
                    ->with('success', 'Elemento completado y kilos actualizados en los productos.');
            }

            if ($validated['accion'] === 'descompletar') {
                $producto = $maquina->productos()->where('diametro', $elemento->diametro)->first();

                if (!$producto) {
                    throw new \Exception('No se encontró un producto asociado con ese diámetro en la máquina.');
                }

                // Revertir los kilos al producto
                $producto->peso_stock += $elemento->peso;
                $producto->save();

                // Actualizar el estado del elemento
                $elemento->estado = 'pendiente';
                $elemento->users_id = null;
                $elemento->save();

                DB::commit();

                return redirect()->route('elementos.show', $planilla->id)
                    ->with('success', 'Elemento descompletado y kilos revertidos al producto.');
            }

            throw new \Exception('Acción no válida.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('elementos.show', $planilla->id)
                ->with('error', $e->getMessage());
        }
    }

    public function actualizarElemento(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $elemento = Elemento::findOrFail($id);
            $maquina = $elemento->maquina;

            if (!$maquina) {
                return response()->json([
                    'success' => false,
                    'error' => 'La máquina asociada al elemento no existe.',
                ], 404);
            }

            $productos = collect(); // Inicializa la colección vacía para evitar el error


            if ($elemento->estado == "pendiente") {
                $elemento->estado = "fabricando";
                $elemento->fecha_inicio = now();
                $elemento->users_id = Auth::id();

                $elemento->users_id_2 = session()->get('compañero_id', null);
            } elseif ($elemento->estado == "fabricando") {
                $productos = collect($maquina->productos()->where('diametro', $elemento->diametro)->orderBy('peso_stock')->get());

                if ($productos->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'En esta máquina no hay materia prima con ese diámetro.',
                    ], 400);
                }

                $pesoRequerido = $elemento->peso;

                foreach ($productos as $prod) {
                    if ($pesoRequerido <= 0) {
                        break;
                    }

                    $pesoDisponible = $prod->peso_stock;

                    if ($pesoDisponible > 0) {
                        $resta = min($pesoDisponible, $pesoRequerido);
                        $prod->peso_stock -= $resta;

                        if ($prod->peso_stock == 0) {
                            $prod->estado = "consumido";
                        }

                        $prod->save();
                        $pesoRequerido -= $resta;
                        $producto = $prod; // Último producto del que se tomó material
                    }
                }

                if ($pesoRequerido > 0) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No hay suficiente materia prima. Avisa al gruista',
                    ], 400);
                }

                $elemento->fecha_finalizacion = now();
                $elemento->estado = 'completado';
                $elemento->producto_id = $producto->id ?? null;
            } elseif ($elemento->estado == "completado") {
                $productos = collect($maquina->productos()->where('diametro', $elemento->diametro)->orderBy('peso_stock', 'desc')->get());
                $productosAfectados = collect(); // Inicializa colección vacía
                if ($productos->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No hay materia prima con el diámetro especificado.',
                    ], 400);
                }

                $pesoRestante = $elemento->peso;

                foreach ($productos as $prod) {
                    if ($pesoRestante <= 0) {
                        break;
                    }
                    $incremento = min($pesoRestante, $prod->peso_inicial - $prod->peso_stock);
                    $prod->peso_stock += $incremento;
                    $pesoRestante -= $incremento;
                    $prod->estado = "fabricando";
                    $prod->save();
                    // Guardamos manualmente el producto modificado
                    $productosAfectados->push([
                        'id' => $prod->id,
                        'peso_stock' => $prod->peso_stock,
                        'peso_inicial' => $prod->peso_inicial
                    ]);
                }
                $elemento->fecha_inicio = null;
                $elemento->fecha_finalizacion = null;
                $elemento->estado = "pendiente";
                $elemento->users_id = null;
                $elemento->users_id_2 = null;
            }

            $fechaInicio = $elemento->fecha_inicio ? Carbon::parse($elemento->fecha_inicio) : null;
            $fechaFinalizacion = $elemento->fecha_finalizacion ? Carbon::parse($elemento->fecha_finalizacion) : null;

            $tiempoReal = null;
            $emoji = "❓";

            if ($fechaInicio && $fechaFinalizacion) {
                $tiempoReal = $fechaInicio->diffInSeconds($fechaFinalizacion);
                $tiempoEstimado = $elemento->tiempo_fabricacion ?? 0;
                $emoji = ($tiempoReal <= $tiempoEstimado) ? "😊" : "😢";
            }

            $elemento->save();
            DB::commit();

            $productosAfectados = $productos
                ? $productos->filter(fn($p) => $p->peso_stock != $p->peso_inicial)->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'peso_stock' => $p->peso_stock,
                        'peso_inicial' => $p->peso_inicial
                    ];
                })->values()->all() // Esto devuelve un array en lugar de una colección
                : [];

            return response()->json([
                'success' => true,
                'estado' => $elemento->estado,
                'fecha_inicio' => $fechaInicio ? $fechaInicio->format('d/m/Y H:i:s') : 'No asignada',
                'fecha_finalizacion' => $fechaFinalizacion ? $fechaFinalizacion->format('d/m/Y H:i:s') : 'No asignada',
                'emoji' => $emoji,
                // ''peso_stock' => $producto ? $producto->peso_stock : null,
                // 'peso_inicial' => $producto ? $producto->peso_inicial : null,
                // 'producto_id' => $producto ? $producto->id : null'
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
