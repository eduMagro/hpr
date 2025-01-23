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
    public function crearConjunto(Request $request) {}


    public function actualizarEtiqueta(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $etiqueta = Etiqueta::with('elementos')->findOrFail($id);
            $maquina = $etiqueta->elementos->first()->maquina;

            if (!$maquina) {

                return response()->json([
                    'success' => false,
                    'error' => 'La máquina asociada al elemento no existe.',
                ], 404);
            }

            $productos = collect(); // Inicializa la colección vacía para evitar el error


            if ($etiqueta->estado == "pendiente") {
                $etiqueta->estado = "fabricando";
                $etiqueta->fecha_inicio = now();
                $etiqueta->users_id_1 = Auth::id();
                $etiqueta->users_id_2 = session()->get('compañero_id', null);
                $primerProducto = null;
                $segundoProducto = null;
            } elseif ($etiqueta->estado == "fabricando") {
                $productos = $maquina->productos()->where('diametro', $etiqueta->elementos->first()->diametro)->orderBy('peso_stock')->get();


                if ($productos->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'En esta máquina no hay materia prima con ese diámetro.',
                    ], 400);
                }

                $pesoRequerido = $etiqueta->peso;

                    // ✅ Si no se requiere peso, no es necesario continuar
                    if ($pesoRequerido <= 0) {
                        return response()->json([
                            'success' => false,
                            'error' => 'El peso requerido es 0, no es necesario consumir materia prima.',
                        ], 400);
                    }


                $primerProducto = null;
                $segundoProducto = null;
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
                        $productosConsumidos[] = $prod; // Guardar producto consumido
                        $pesoRequerido -= $restar;
                    }
                }

                if ($pesoRequerido > 0) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No hay suficiente materia prima. Avisa al gruista',
                    ], 400);
                }

                $etiqueta->fecha_finalizacion = now();
                $etiqueta->estado = 'completado';
                 // Asignar producto_id y producto_id_2 solo si existen productos consumidos
    $etiqueta->producto_id = isset($productosConsumidos[0]) ? $productosConsumidos[0]->id : null;
    $etiqueta->producto_id_2 = isset($productosConsumidos[1]) ? $productosConsumidos[1]->id : null;
            } elseif ($etiqueta->estado == "completado") {
                $productos = collect($maquina->productos()->where('diametro', $etiqueta->elemento->diametro)->orderBy('peso_stock', 'desc')->get());

                if ($productos->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No hay materia prima con el diámetro especificado.',
                    ], 400);
                }

                $pesoRestante = $etiqueta->peso;

                foreach ($productos as $prod) {
                    if ($pesoRestante <= 0) {
                        break;
                    }
                    $incremento = min($pesoRestante, $prod->peso_inicial - $prod->peso_stock);
                    $prod->peso_stock += $incremento;
                    $pesoRestante -= $incremento;
                    $prod->estado = "fabricando";
                    $prod->save();
                }
                $etiqueta->fecha_inicio = null;
                $etiqueta->fecha_finalizacion = null;
                $etiqueta->estado = "pendiente";
                $etiqueta->users_id_1 = null;
                $etiqueta->users_id_2 = null;
                $etiqueta->producto_id = null;
                $etiqueta->producto_id_2 = null;
            }

            $fechaInicio = $etiqueta->fecha_inicio ? Carbon::parse($etiqueta->fecha_inicio) : null;
            $fechaFinalizacion = $etiqueta->fecha_finalizacion ? Carbon::parse($etiqueta->fecha_finalizacion) : null;

            $tiempoReal = null;
            $emoji = "❓";

            if ($fechaInicio && $fechaFinalizacion) {
                $tiempoReal = $fechaInicio->diffInSeconds($fechaFinalizacion);
                $tiempoEstimado = $etiqueta->tiempo_fabricacion ?? 0;
                $emoji = ($tiempoReal <= $tiempoEstimado) ? "😊" : "😢";
            }

            $etiqueta->save();
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
                'estado' => $etiqueta->estado,
                'fecha_inicio' => $fechaInicio ? $fechaInicio->format('d/m/Y H:i:s') : 'No asignada',
                'fecha_finalizacion' => $fechaFinalizacion ? $fechaFinalizacion->format('d/m/Y H:i:s') : 'No asignada',
                'emoji' => $emoji,
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
