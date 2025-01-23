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
            $primerElemento = $etiqueta->elementos->first();
    
            if (!$primerElemento) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se encontraron elementos asociados a esta etiqueta.',
                ], 400);
            }
    
            $maquina = $primerElemento->maquina;
    
            if (!$maquina) {
                return response()->json([
                    'success' => false,
                    'error' => 'La máquina asociada al elemento no existe.',
                ], 404);
            }
    
            $productosConsumidos = [];
            $producto1 = null;
            $producto2 = null;
    
            if ($etiqueta->estado == "pendiente") {
                $etiqueta->estado = "fabricando";
                $etiqueta->fecha_inicio = now();
                $etiqueta->users_id_1 = Auth::id();
                $etiqueta->users_id_2 = session()->get('compañero_id', null);
                $etiqueta->save(); // ✅ Guardamos cambios
    
            } elseif ($etiqueta->estado == "fabricando") {
                $productos = $maquina->productos()
                    ->where('diametro', $primerElemento->diametro)
                    ->orderBy('peso_stock')
                    ->get();
    
                if ($productos->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'En esta máquina no hay materia prima con ese diámetro.',
                    ], 400);
                }
    
                $pesoRequerido = $etiqueta->peso;
    
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
    
                $etiqueta->fecha_finalizacion = now();
                $etiqueta->estado = 'completado';
    
                $etiqueta->producto_id = $productosConsumidos[0]->id ?? null;
                $etiqueta->producto_id_2 = $productosConsumidos[1]->id ?? null;
                $etiqueta->save(); // ✅ Guardamos cambios
    
            } elseif ($etiqueta->estado == "completado") {
                $producto1 = $etiqueta->producto_id ? $maquina->productos()->find($etiqueta->producto_id) : null;
                $producto2 = $etiqueta->producto_id_2 ? $maquina->productos()->find($etiqueta->producto_id_2) : null;
    
                if (!$producto1 && !$producto2) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No se encontraron los productos consumidos para restaurar.',
                    ], 400);
                }
    
                $pesoRestaurar = $etiqueta->peso;
    
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
                $etiqueta->fecha_inicio = null;
                $etiqueta->fecha_finalizacion = null;
                $etiqueta->estado = "pendiente";
                $etiqueta->users_id_1 = null;
                $etiqueta->users_id_2 = null;
                $etiqueta->producto_id = null;
                $etiqueta->producto_id_2 = null;
                $etiqueta->save(); // ✅ Guardamos cambios
            }
    
            DB::commit();
    
            // ✅ **Aquí colocamos el código para enviar `productos_afectados` correctamente**
            $productosAfectados = collect([$producto1, $producto2])
                ->filter() // ✅ Elimina valores nulos
                ->map(fn($p) => [
                    'id' => $p->id,
                    'peso_stock' => $p->peso_stock,
                    'peso_inicial' => $p->peso_inicial
                ])
                ->values()
                ->all();
    
                return response()->json([
                    'success' => true,
                    'estado' => $etiqueta->estado,
                    'fecha_inicio' => $etiqueta->fecha_inicio ? Carbon::parse($etiqueta->fecha_inicio)->format('d/m/Y H:i:s') : 'No asignada',
                    'fecha_finalizacion' => $etiqueta->fecha_finalizacion ? Carbon::parse($etiqueta->fecha_finalizacion)->format('d/m/Y H:i:s') : 'No asignada',
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
